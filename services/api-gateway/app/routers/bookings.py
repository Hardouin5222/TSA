import httpx
from fastapi import APIRouter, HTTPException, Query, status

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


@router.get("/reference/{booking_reference}")
async def get_booking_by_reference(booking_reference: str) -> dict:
    target_url = f"{settings.booking_service_base_url}{settings.api_prefix}/bookings/reference/{booking_reference}"

    async with httpx.AsyncClient(timeout=20.0) as client:
        response = await client.get(target_url)

    if response.status_code >= 400:
        raise HTTPException(status_code=status.HTTP_502_BAD_GATEWAY, detail="Booking service request failed")

    return response.json()


@router.get("/")
async def list_bookings(user_id: str | None = Query(default=None), guest_session_id: str | None = Query(default=None)) -> dict:
    target_url = f"{settings.booking_service_base_url}{settings.api_prefix}/bookings"

    async with httpx.AsyncClient(timeout=20.0) as client:
        response = await client.get(
            target_url,
            params={"user_id": user_id, "guest_session_id": guest_session_id},
        )

    if response.status_code >= 400:
        raise HTTPException(status_code=status.HTTP_502_BAD_GATEWAY, detail="Booking service request failed")

    return response.json()
