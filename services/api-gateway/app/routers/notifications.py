import httpx
from fastapi import APIRouter, HTTPException, Query, status

from app.core.settings import get_gateway_settings

router = APIRouter(prefix="/notifications", tags=["notifications"])
settings = get_gateway_settings()


@router.post("/booking-confirmations")
async def create_booking_confirmation(payload: dict) -> dict:
    target_url = (
        f"{settings.notification_service_base_url}{settings.api_prefix}/notifications/booking-confirmations"
    )

    async with httpx.AsyncClient(timeout=20.0) as client:
        response = await client.post(target_url, json=payload)

    if response.status_code >= 400:
        detail = response.json().get("detail", "Notification service request failed")
        raise HTTPException(status_code=status.HTTP_502_BAD_GATEWAY, detail=detail)

    return response.json()


@router.get("/")
async def list_notifications(
    booking_reference: str | None = Query(default=None),
    user_id: str | None = Query(default=None),
    recipient_email: str | None = Query(default=None),
) -> dict:
    target_url = f"{settings.notification_service_base_url}{settings.api_prefix}/notifications/"

    async with httpx.AsyncClient(timeout=20.0) as client:
        response = await client.get(
            target_url,
            params={
                "booking_reference": booking_reference,
                "user_id": user_id,
                "recipient_email": recipient_email,
            },
        )

    if response.status_code >= 400:
        detail = response.json().get("detail", "Notification service request failed")
        raise HTTPException(status_code=status.HTTP_502_BAD_GATEWAY, detail=detail)

    return response.json()


@router.post("/claim-guest")
async def claim_guest_notifications(payload: dict) -> dict:
    target_url = f"{settings.notification_service_base_url}{settings.api_prefix}/notifications/claim-guest"

    async with httpx.AsyncClient(timeout=20.0) as client:
        response = await client.post(target_url, json=payload)

    if response.status_code >= 400:
        detail = response.json().get("detail", "Notification service request failed")
        raise HTTPException(status_code=status.HTTP_502_BAD_GATEWAY, detail=detail)

    return response.json()


@router.post("/{notification_id}/dispatch-mock")
async def dispatch_notification(notification_id: str) -> dict:
    target_url = (
        f"{settings.notification_service_base_url}{settings.api_prefix}/notifications/{notification_id}/dispatch-mock"
    )

    async with httpx.AsyncClient(timeout=20.0) as client:
        response = await client.post(target_url)

    if response.status_code >= 400:
        detail = response.json().get("detail", "Notification service request failed")
        raise HTTPException(status_code=status.HTTP_502_BAD_GATEWAY, detail=detail)

    return response.json()
