import httpx
from fastapi import APIRouter, HTTPException, status

from app.core.settings import get_gateway_settings

router = APIRouter(prefix="/flights", tags=["flights"])
settings = get_gateway_settings()


@router.post("/search")
async def search_flights(payload: dict) -> dict:
    target_url = f"{settings.flight_service_base_url}{settings.api_prefix}/flights/search"

    async with httpx.AsyncClient(timeout=20.0) as client:
      response = await client.post(target_url, json=payload)

    if response.status_code >= 400:
        raise HTTPException(
            status_code=status.HTTP_502_BAD_GATEWAY,
            detail="Flight service request failed",
        )

    return response.json()
