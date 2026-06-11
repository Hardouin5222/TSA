from typing import Annotated

import jwt
from fastapi import Depends, Header, HTTPException, status
from sqlalchemy.orm import Session

from app.core.database import get_db_session
from app.core.settings import get_cart_service_settings

settings = get_cart_service_settings()

DbSession = Annotated[Session, Depends(get_db_session)]


def get_optional_user_id(authorization: Annotated[str | None, Header()] = None) -> str | None:
    if not authorization:
        return None

    if not authorization.startswith("Bearer "):
        raise HTTPException(status_code=status.HTTP_401_UNAUTHORIZED, detail="Invalid authorization header")

    token = authorization.removeprefix("Bearer ").strip()

    try:
        payload = jwt.decode(token, settings.jwt_secret_key, algorithms=[settings.jwt_algorithm])
    except jwt.InvalidTokenError as exc:
        raise HTTPException(status_code=status.HTTP_401_UNAUTHORIZED, detail="Invalid access token") from exc

    return str(payload.get("sub")) if payload.get("sub") else None
