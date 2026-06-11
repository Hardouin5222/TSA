import httpx
from fastapi import APIRouter, HTTPException, status

from app.core.settings import get_gateway_settings

router = APIRouter(prefix="/bookings", tags=["bookings"])
settings = get_gateway_settings()


@router.post("/from-payment")
async def create_booking_from_payment(payload: dict) -> dict:
    target_url = f"{settings.booking_service_base_url}{settings.api_prefix}/bookings/from-payment"

    async with httpx.AsyncClient(timeout=20.0) as client:
        response = await client.post(target_url, json=payload)

    if response.status_code >= 400:
        raise HTTPException(status_code=status.HTTP_502_BAD_GATEWAY, detail="Booking service request failed")

    return response.json()
