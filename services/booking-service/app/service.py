from uuid import uuid4

from sqlalchemy import select
from sqlalchemy.orm import Session

from app.models import Booking, BookingItem
from app.schemas import BookingResponse, CreateBookingFromPaymentRequest


def create_booking_from_payment(payload: CreateBookingFromPaymentRequest, db: Session) -> BookingResponse:
    existing = db.scalar(select(Booking).where(Booking.payment_intent_id == payload.payment_intent_id))
    if existing:
        item_count = len(db.scalars(select(BookingItem).where(BookingItem.booking_id == existing.id)).all())
        return BookingResponse(
            booking_id=str(existing.id),
            booking_reference=existing.booking_reference,
            status=existing.status,
            total_amount=float(existing.total_amount),
            currency=existing.currency,
            item_count=item_count,
        )

    booking = Booking(
      booking_reference=f"TSA-{uuid4().hex[:8].upper()}",
      payment_intent_id=payload.payment_intent_id,
      provider_reference=payload.provider_reference,
      cart_id=payload.cart_id,
      user_id=payload.user_id,
      guest_session_id=payload.guest_session_id,
      status="confirmed",
      total_amount=payload.total_amount,
      currency=payload.currency,
    )
    db.add(booking)
    db.flush()

    for item in payload.items:
        db.add(
            BookingItem(
                booking_id=booking.id,
                item_type=item.item_type,
                reference_id=item.reference_id,
                title=item.title,
                quantity=item.quantity,
                unit_price=item.unit_price,
                currency=item.currency,
                item_payload=item.item_payload,
            )
        )

    db.commit()
    db.refresh(booking)

    return BookingResponse(
        booking_id=str(booking.id),
        booking_reference=booking.booking_reference,
        status=booking.status,
        total_amount=float(booking.total_amount),
        currency=booking.currency,
        item_count=len(payload.items),
    )
