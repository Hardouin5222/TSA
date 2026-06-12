from typing import Annotated

from fastapi import APIRouter, Depends, Query
from sqlalchemy.orm import Session

from app.core.database import get_db_session
from app.schemas import ClaimGuestNotificationRequest, CreateBookingConfirmationNotificationRequest
from app.service import (
    claim_guest_notifications,
    create_booking_confirmation_notification,
    dispatch_notification,
    get_notification_detail,
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
    recipient_email: str | None = Query(default=None),
    db: Session = Depends(get_db_session),
) -> dict:
    result = list_notifications(
        booking_reference=booking_reference,
        user_id=user_id,
        recipient_email=recipient_email,
        db=db,
    )
    return success_response(result.model_dump(), message="Notifications fetched successfully")


@router.post("/claim-guest")
async def claim_guest_notifications_route(payload: ClaimGuestNotificationRequest, db: DbSession) -> dict:
    result = claim_guest_notifications(payload, db)
    return success_response(result.model_dump(), message="Guest notifications claimed successfully")


@router.get("/{notification_id}")
async def get_notification_detail_route(notification_id: str, db: DbSession) -> dict:
    result = get_notification_detail(notification_id, db)
    return success_response(result.model_dump(), message="Notification fetched successfully")


@router.post("/{notification_id}/dispatch-mock")
async def dispatch_notification_route(notification_id: str, db: DbSession) -> dict:
    result = dispatch_notification(notification_id, db)
    return success_response(result.model_dump(), message="Notification dispatched successfully")
