from typing import Annotated

from fastapi import APIRouter, Depends, Query
from sqlalchemy.orm import Session

from app.core.database import get_db_session
from app.schemas import ClaimGuestNotificationRequest, CreateBookingConfirmationNotificationRequest
from app.service import (
    claim_guest_notifications,
    create_booking_confirmation_notification,
    list_notifications,
)
from travel_shared.responses import success_response

router = APIRouter(prefix="/notifications", tags=["notifications"])
DbSession = Annotated[Session, Depends(get_db_session)]


@router.post("/booking-confirmations")
async def create_booking_confirmation(payload: CreateBookingConfirmationNotificationRequest, db: DbSession) -> dict:
    result = create_booking_confirmation_notification(payload, db)
    return success_response(result.model_dump(), message="Booking confirmation notification queued")


@router.get("/")
async def get_notifications(
    booking_reference: str | None = Query(default=None),
    user_id: str | None = Query(default=None),
    db: DbSession = Depends(get_db_session),
) -> dict:
    result = list_notifications(booking_reference=booking_reference, user_id=user_id, db=db)
    return success_response(result.model_dump(), message="Notifications fetched successfully")


@router.post("/claim-guest")
async def claim_guest_notifications_route(payload: ClaimGuestNotificationRequest, db: DbSession) -> dict:
    result = claim_guest_notifications(payload, db)
    return success_response(result.model_dump(), message="Guest notifications claimed successfully")
