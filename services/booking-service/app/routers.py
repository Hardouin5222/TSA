from typing import Annotated

from fastapi import APIRouter, Depends
from sqlalchemy.orm import Session

from app.core.database import get_db_session
from app.schemas import CreateBookingFromPaymentRequest
from app.service import create_booking_from_payment
from travel_shared.responses import success_response

router = APIRouter(prefix="/bookings", tags=["bookings"])
DbSession = Annotated[Session, Depends(get_db_session)]


@router.post("/from-payment")
async def create_from_payment(payload: CreateBookingFromPaymentRequest, db: DbSession) -> dict:
    result = create_booking_from_payment(payload, db)
    return success_response(result.model_dump(), message="Booking created successfully")
