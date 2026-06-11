from typing import Annotated

from fastapi import APIRouter, Depends, Query
from sqlalchemy.orm import Session

from app.core.database import get_db_session
from app.schemas import ClaimGuestBookingRequest, CreateBookingFromPaymentRequest
from app.service import claim_guest_bookings, create_booking_from_payment, get_booking_by_reference, list_bookings
from travel_shared.responses import success_response

router = APIRouter(prefix="/bookings", tags=["bookings"])
DbSession = Annotated[Session, Depends(get_db_session)]


@router.post("/from-payment")
async def create_from_payment(payload: CreateBookingFromPaymentRequest, db: DbSession) -> dict:
    result = create_booking_from_payment(payload, db)
    return success_response(result.model_dump(), message="Booking created successfully")


@router.get("/reference/{booking_reference}")
async def get_by_reference(booking_reference: str, db: DbSession) -> dict:
    result = get_booking_by_reference(booking_reference, db)
    return success_response(result.model_dump(), message="Booking fetched successfully")


@router.get("/")
async def get_bookings(
    db: DbSession,
    user_id: str | None = Query(default=None),
    guest_session_id: str | None = Query(default=None),
) -> dict:
    result = list_bookings(user_id, guest_session_id, db)
    return success_response(result.model_dump(), message="Bookings fetched successfully")


@router.post("/claim-guest")
async def claim_guest(payload: ClaimGuestBookingRequest, db: DbSession) -> dict:
    result = claim_guest_bookings(payload, db)
    return success_response(result.model_dump(), message="Guest bookings claimed successfully")
