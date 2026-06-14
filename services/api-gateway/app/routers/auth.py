import httpx
from fastapi import APIRouter, Header, HTTPException, status

from app.core.settings import get_gateway_settings

router = APIRouter(prefix="/auth", tags=["auth"])
settings = get_gateway_settings()


async def _forward_auth_request(path: str, payload: dict, guest_session_id: str | None = None) -> dict:
    target_url = f"{settings.user_service_base_url}{settings.api_prefix}/auth/{path}"
    headers = {"Content-Type": "application/json"}
    if guest_session_id:
        headers["X-Guest-Session-Id"] = guest_session_id

    async with httpx.AsyncClient(timeout=20.0) as client:
        response = await client.post(target_url, json=payload, headers=headers)

    if response.status_code >= 400:
        detail = response.json().get("detail", "User service request failed")
        raise HTTPException(status_code=response.status_code, detail=detail)

    return response.json()


@router.post("/register")
async def register(payload: dict, x_guest_session_id: str | None = Header(default=None)) -> dict:
    return await _forward_auth_request("register", payload, x_guest_session_id)


@router.post("/login")
async def login(payload: dict, x_guest_session_id: str | None = Header(default=None)) -> dict:
    return await _forward_auth_request("login", payload, x_guest_session_id)


@router.post("/refresh")
async def refresh(payload: dict) -> dict:
    return await _forward_auth_request("refresh", payload)


@router.post("/logout")
async def logout(payload: dict) -> dict:
    return await _forward_auth_request("logout", payload)


@router.post("/password-reset/request")
async def password_reset_request(payload: dict) -> dict:
    return await _forward_auth_request("password-reset/request", payload)


@router.post("/password-reset/confirm")
async def password_reset_confirm(payload: dict) -> dict:
    return await _forward_auth_request("password-reset/confirm", payload)
