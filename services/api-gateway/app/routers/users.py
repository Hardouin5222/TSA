import httpx
from fastapi import APIRouter, Header, HTTPException

from app.core.settings import get_gateway_settings

router = APIRouter(prefix="/users", tags=["users"])
settings = get_gateway_settings()


@router.get("/me")
async def get_current_user(authorization: str | None = Header(default=None)) -> dict:
    target_url = f"{settings.user_service_base_url}{settings.api_prefix}/users/me"
    headers: dict[str, str] = {}
    if authorization:
        headers["Authorization"] = authorization

    async with httpx.AsyncClient(timeout=20.0) as client:
        response = await client.get(target_url, headers=headers)

    if response.status_code >= 400:
        detail = response.json().get("detail", "User service request failed")
        raise HTTPException(status_code=response.status_code, detail=detail)

    return response.json()
