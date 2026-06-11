from datetime import UTC, datetime, timedelta

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
from app.models.user import User, UserSession
from app.schemas.auth import (
    AuthResponse,
    AuthenticatedUserResponse,
    LoginRequest,
    RegisterRequest,
    TokenResponse,
)

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

    tokens = _create_session_tokens(user, db, request)
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
