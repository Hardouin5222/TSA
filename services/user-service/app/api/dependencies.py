from typing import Annotated

from fastapi import Depends, HTTPException, status
from fastapi.security import HTTPAuthorizationCredentials, HTTPBearer
from sqlalchemy.orm import Session

from app.core.database import get_db_session
from app.models.user import User
from app.services.auth_service import get_current_user_from_token

bearer_scheme = HTTPBearer(auto_error=False)


DbSession = Annotated[Session, Depends(get_db_session)]


def get_authenticated_user(
    credentials: Annotated[HTTPAuthorizationCredentials | None, Depends(bearer_scheme)],
    db: DbSession,
) -> User:
    if credentials is None:
        raise HTTPException(status_code=status.HTTP_401_UNAUTHORIZED, detail="Authentication required")
    return get_current_user_from_token(credentials.credentials, db)
