from fastapi import APIRouter, Request, status

from app.api.dependencies import DbSession
from app.schemas.auth import AuthResponse, LoginRequest, RegisterRequest
from app.services.auth_service import login_user, register_user
from travel_shared.responses import success_response

router = APIRouter(prefix="/auth", tags=["auth"])


@router.post("/register", status_code=status.HTTP_201_CREATED)
async def register(payload: RegisterRequest, request: Request, db: DbSession) -> dict:
    result = register_user(payload, db, request)
    return success_response(result.model_dump(), message="User registered successfully")


@router.post("/login")
async def login(payload: LoginRequest, request: Request, db: DbSession) -> dict:
    result = login_user(payload, db, request)
    return success_response(result.model_dump(), message="Login successful")
