from datetime import UTC, datetime, timedelta
from hashlib import sha256
from secrets import token_urlsafe

import jwt
from fastapi import HTTPException, Request, status
from sqlalchemy import select
from sqlalchemy.orm import Session

from app.core.security import (
    create_access_token,
    create_refresh_token,
    decode_access_token,
    hash_password,
    verify_password,
)
from app.core.settings import get_user_service_settings
from app.models.password_reset import PasswordResetToken
from app.models.rbac import Permission, Role, RolePermission, UserRole
from app.models.user import User, UserSession
from app.schemas.auth import (
    AuthResponse,
    AuthenticatedUserResponse,
    LoginRequest,
    PasswordResetConfirmRequest,
    PasswordResetRequest,
    RefreshTokenRequest,
    RegisterRequest,
    TokenResponse,
)
from app.services.audit_service import create_audit_log

settings = get_user_service_settings()


def register_user(payload: RegisterRequest, db: Session, request: Request) -> AuthResponse:
    existing_user = db.scalar(select(User).where(User.email == payload.email.lower()))
    if existing_user:
        raise HTTPException(status_code=status.HTTP_409_CONFLICT, detail="Email already registered")

    user = User(
        email=payload.email.lower(),
        password_hash=hash_password(payload.password),
        first_name=payload.first_name.strip(),
        last_name=payload.last_name.strip(),
        phone_number=payload.phone_number,
    )
    db.add(user)
    db.flush()
    _assign_default_customer_role(user.id, db)

    tokens = _create_session_tokens(user, db, request)
    create_audit_log(
        db,
        event_type="auth.registered",
        entity_type="user",
        entity_id=str(user.id),
        actor_user_id=str(user.id),
        request=request,
        metadata={"email": user.email},
    )
    db.commit()
    db.refresh(user)

    return AuthResponse(
        user=AuthenticatedUserResponse.model_validate(user),
        tokens=tokens,
    )


def login_user(payload: LoginRequest, db: Session, request: Request) -> AuthResponse:
    user = db.scalar(select(User).where(User.email == payload.email.lower(), User.deleted_at.is_(None)))
    if not user or not verify_password(payload.password, user.password_hash):
        raise HTTPException(status_code=status.HTTP_401_UNAUTHORIZED, detail="Invalid credentials")

    user.last_login_at = datetime.now(UTC)
    tokens = _create_session_tokens(user, db, request)
    create_audit_log(
        db,
        event_type="auth.logged_in",
        entity_type="user",
        entity_id=str(user.id),
        actor_user_id=str(user.id),
        request=request,
    )
    db.commit()
    db.refresh(user)

    return AuthResponse(
        user=AuthenticatedUserResponse.model_validate(user),
        tokens=tokens,
    )


def get_current_user_from_token(token: str, db: Session) -> User:
    try:
        payload = decode_access_token(token)
    except jwt.InvalidTokenError as exc:
        raise HTTPException(status_code=status.HTTP_401_UNAUTHORIZED, detail="Invalid access token") from exc

    if payload.get("type") != "access":
        raise HTTPException(status_code=status.HTTP_401_UNAUTHORIZED, detail="Invalid token type")

    user = db.get(User, payload["sub"])
    if not user or user.deleted_at is not None:
        raise HTTPException(status_code=status.HTTP_401_UNAUTHORIZED, detail="User not found")
    return user


def refresh_user_tokens(payload: RefreshTokenRequest, db: Session, request: Request) -> TokenResponse:
    try:
        token_payload = jwt.decode(
            payload.refresh_token,
            settings.jwt_refresh_secret_key,
            algorithms=[settings.jwt_algorithm],
        )
    except jwt.InvalidTokenError as exc:
        raise HTTPException(status_code=status.HTTP_401_UNAUTHORIZED, detail="Invalid refresh token") from exc

    if token_payload.get("type") != "refresh":
        raise HTTPException(status_code=status.HTTP_401_UNAUTHORIZED, detail="Invalid token type")

    session = db.get(UserSession, token_payload["sid"])
    if not session:
        raise HTTPException(status_code=status.HTTP_401_UNAUTHORIZED, detail="Session not found")
    if session.revoked_at is not None:
        raise HTTPException(status_code=status.HTTP_401_UNAUTHORIZED, detail="Session revoked")
    if session.expires_at <= datetime.now(UTC):
        raise HTTPException(status_code=status.HTTP_401_UNAUTHORIZED, detail="Session expired")
    if session.refresh_token_jti != token_payload.get("jti"):
        raise HTTPException(status_code=status.HTTP_401_UNAUTHORIZED, detail="Refresh token rotated")

    user = db.get(User, session.user_id)
    if not user or user.deleted_at is not None:
        raise HTTPException(status_code=status.HTTP_401_UNAUTHORIZED, detail="User not found")

    session.revoked_at = datetime.now(UTC)
    tokens = _create_session_tokens(user, db, request)
    create_audit_log(
        db,
        event_type="auth.tokens_refreshed",
        entity_type="user_session",
        entity_id=str(session.id),
        actor_user_id=str(user.id),
        request=request,
    )
    db.commit()
    return tokens


def logout_user(payload: RefreshTokenRequest, db: Session, request: Request | None = None) -> None:
    try:
        token_payload = jwt.decode(
            payload.refresh_token,
            settings.jwt_refresh_secret_key,
            algorithms=[settings.jwt_algorithm],
        )
    except jwt.InvalidTokenError as exc:
        raise HTTPException(status_code=status.HTTP_401_UNAUTHORIZED, detail="Invalid refresh token") from exc

    session = db.get(UserSession, token_payload["sid"])
    if not session:
        raise HTTPException(status_code=status.HTTP_401_UNAUTHORIZED, detail="Session not found")

    session.revoked_at = datetime.now(UTC)
    create_audit_log(
        db,
        event_type="auth.logged_out",
        entity_type="user_session",
        entity_id=str(session.id),
        actor_user_id=str(session.user_id),
        request=request,
    )
    db.commit()


def request_password_reset(payload: PasswordResetRequest, db: Session, request: Request) -> None:
    user = db.scalar(select(User).where(User.email == payload.email.lower(), User.deleted_at.is_(None)))
    if user:
        reset_token = token_urlsafe(32)
        reset_token_hash = _hash_reset_token(reset_token)

        db.execute(
            PasswordResetToken.__table__.update()
            .where(PasswordResetToken.user_id == user.id, PasswordResetToken.used_at.is_(None))
            .values(is_revoked=True, updated_at=datetime.now(UTC))
        )

        db.add(
            PasswordResetToken(
                user_id=user.id,
                token_hash=reset_token_hash,
                expires_at=datetime.now(UTC) + timedelta(minutes=30),
            )
        )
        create_audit_log(
            db,
            event_type="auth.password_reset_requested",
            entity_type="user",
            entity_id=str(user.id),
            actor_user_id=str(user.id),
            request=request,
        )
        db.commit()


def confirm_password_reset(payload: PasswordResetConfirmRequest, db: Session, request: Request) -> None:
    hashed_token = _hash_reset_token(payload.token)
    reset_record = db.scalar(
        select(PasswordResetToken).where(
            PasswordResetToken.token_hash == hashed_token,
            PasswordResetToken.used_at.is_(None),
            PasswordResetToken.is_revoked.is_(False),
        )
    )
    if not reset_record or reset_record.expires_at <= datetime.now(UTC):
        raise HTTPException(status_code=status.HTTP_400_BAD_REQUEST, detail="Invalid or expired reset token")

    user = db.get(User, reset_record.user_id)
    if not user or user.deleted_at is not None:
        raise HTTPException(status_code=status.HTTP_400_BAD_REQUEST, detail="User not found")

    user.password_hash = hash_password(payload.new_password)
    reset_record.used_at = datetime.now(UTC)
    create_audit_log(
        db,
        event_type="auth.password_reset_completed",
        entity_type="user",
        entity_id=str(user.id),
        actor_user_id=str(user.id),
        request=request,
    )
    db.commit()


def get_user_permissions(user_id: str, db: Session) -> set[str]:
    rows = db.execute(
        select(
            Permission.code,
        )
        .select_from(UserRole)
        .join(RolePermission, RolePermission.role_id == UserRole.role_id)
        .join(Permission, Permission.id == RolePermission.permission_id)
        .where(UserRole.user_id == user_id)
    )
    return {row[0] for row in rows}


def _assign_default_customer_role(user_id: str, db: Session) -> None:
    customer_role = db.scalar(select(Role).where(Role.code == "customer"))
    if not customer_role:
        return

    existing_link = db.scalar(
        select(UserRole).where(UserRole.user_id == user_id, UserRole.role_id == customer_role.id)
    )
    if existing_link:
        return

    db.add(UserRole(user_id=user_id, role_id=customer_role.id))


def _create_session_tokens(user: User, db: Session, request: Request) -> TokenResponse:
    session = UserSession(
        user_id=user.id,
        refresh_token_jti="pending",
        user_agent=request.headers.get("user-agent"),
        ip_address=request.client.host if request.client else None,
        expires_at=datetime.now(UTC) + timedelta(days=settings.refresh_token_expire_days),
    )
    db.add(session)
    db.flush()

    access_token = create_access_token(str(user.id), user.email)
    refresh_token = create_refresh_token(str(session.id), str(user.id))
    refresh_payload = jwt.decode(
        refresh_token,
        settings.jwt_refresh_secret_key,
        algorithms=[settings.jwt_algorithm],
    )
    session.refresh_token_jti = refresh_payload["jti"]
    db.flush()

    return TokenResponse(access_token=access_token, refresh_token=refresh_token)


def _hash_reset_token(token: str) -> str:
    return sha256(token.encode("utf-8")).hexdigest()
