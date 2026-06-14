import httpx
from fastapi import APIRouter, HTTPException, status

from app.core.settings import get_gateway_settings

router = APIRouter(prefix="/cars", tags=["cars"])
settings = get_gateway_settings()


@router.post("/search")
async def search_cars(payload: dict) -> dict:
    target_url = f"{settings.car_rental_service_base_url}{settings.api_prefix}/cars/search"

    async with httpx.AsyncClient(timeout=20.0) as client:
        response = await client.post(target_url, json=payload)

    if response.status_code >= 400:
        raise HTTPException(
            status_code=status.HTTP_502_BAD_GATEWAY,
            detail="Car rental service request failed",
        )

    return response.json()
