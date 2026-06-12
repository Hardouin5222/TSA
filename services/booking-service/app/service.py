import logging
from uuid import uuid4

import httpx
from fastapi import HTTPException, status
from sqlalchemy import select
from sqlalchemy.orm import Session

from app.models import Booking, BookingItem
from app.core.settings import get_booking_service_settings
from app.schemas import (
    BookingBillingDetails,
    BookingContact,
    BookingDetailResponse,
    BookingItemResponse,
    BookingListItemResponse,
    BookingListResponse,
    BookingResponse,
    BookingSpecialRequests,
    BookingTraveler,
    ClaimGuestBookingRequest,
    ClaimGuestBookingResponse,
    CreateBookingFromPaymentRequest,
)

settings = get_booking_service_settings()
logger = logging.getLogger(__name__)


def _get_booking_items(booking_id: str, db: Session) -> list[BookingItem]:
    return db.scalars(select(BookingItem).where(BookingItem.booking_id == booking_id)).all()


def _serialize_booking_item(item: BookingItem) -> BookingItemResponse:
    return BookingItemResponse(
        id=str(item.id),
        item_type=item.item_type,
        reference_id=item.reference_id,
        title=item.title,
        quantity=item.quantity,
        unit_price=float(item.unit_price),
        currency=item.currency,
        item_payload=item.item_payload,
    )


def _serialize_booking_summary(booking: Booking, items: list[BookingItem]) -> BookingListItemResponse:
    return BookingListItemResponse(
        booking_id=str(booking.id),
        booking_reference=booking.booking_reference,
        status=booking.status,
        total_amount=float(booking.total_amount),
        currency=booking.currency,
        item_count=len(items),
        created_at=booking.created_at.isoformat(),
        primary_item_title=items[0].title if items else "Travel reservation",
    )


def _serialize_booking_detail(booking: Booking, items: list[BookingItem]) -> BookingDetailResponse:
    checkout_context = items[0].item_payload.get("checkout_context", {}) if items else {}
    return BookingDetailResponse(
        booking_id=str(booking.id),
        booking_reference=booking.booking_reference,
        status=booking.status,
        total_amount=float(booking.total_amount),
        currency=booking.currency,
        item_count=len(items),
        provider_reference=booking.provider_reference,
        cart_id=booking.cart_id,
        user_id=booking.user_id,
        guest_session_id=booking.guest_session_id,
        created_at=booking.created_at.isoformat(),
        items=[_serialize_booking_item(item) for item in items],
        contact=BookingContact(**checkout_context["contact"]) if checkout_context.get("contact") else None,
        travelers=[BookingTraveler(**traveler) for traveler in checkout_context.get("travelers", [])],
        special_requests=BookingSpecialRequests(**checkout_context["special_requests"])
        if checkout_context.get("special_requests")
        else None,
        billing_details=BookingBillingDetails(**checkout_context["billing_details"])
        if checkout_context.get("billing_details")
        else None,
    )


def create_booking_from_payment(payload: CreateBookingFromPaymentRequest, db: Session) -> BookingResponse:
    existing = db.scalar(select(Booking).where(Booking.payment_intent_id == payload.payment_intent_id))
    if existing:
        item_count = len(_get_booking_items(existing.id, db))
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
                item_payload={
                    **item.item_payload,
                    "checkout_context": {
                        "contact": payload.contact.model_dump(),
                        "travelers": [traveler.model_dump() for traveler in payload.travelers],
                        "special_requests": payload.special_requests.model_dump() if payload.special_requests else None,
                        "billing_details": payload.billing_details.model_dump(),
                    },
                },
            )
        )

    db.commit()
    db.refresh(booking)
    _create_booking_confirmation_notification(booking, payload)

    return BookingResponse(
        booking_id=str(booking.id),
        booking_reference=booking.booking_reference,
        status=booking.status,
        total_amount=float(booking.total_amount),
        currency=booking.currency,
        item_count=len(payload.items),
    )


def get_booking_by_reference(booking_reference: str, db: Session) -> BookingDetailResponse:
    booking = db.scalar(select(Booking).where(Booking.booking_reference == booking_reference))
    if not booking:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Booking not found")

    items = _get_booking_items(booking.id, db)
    return _serialize_booking_detail(booking, items)


def list_bookings(user_id: str | None, guest_session_id: str | None, db: Session) -> BookingListResponse:
    if not user_id and not guest_session_id:
        raise HTTPException(
            status_code=status.HTTP_400_BAD_REQUEST,
            detail="user_id or guest_session_id is required",
        )

    query = select(Booking).order_by(Booking.created_at.desc())
    if user_id:
        query = query.where(Booking.user_id == user_id)
    else:
        query = query.where(Booking.guest_session_id == guest_session_id)

    bookings = db.scalars(query).all()
    summaries = [_serialize_booking_summary(booking, _get_booking_items(booking.id, db)) for booking in bookings]
    return BookingListResponse(bookings=summaries)


def claim_guest_bookings(payload: ClaimGuestBookingRequest, db: Session) -> ClaimGuestBookingResponse:
    guest_bookings = db.scalars(
        select(Booking).where(
            Booking.guest_session_id == payload.guest_session_id,
            Booking.user_id.is_(None),
        )
    ).all()

    for booking in guest_bookings:
        booking.user_id = payload.user_id
        booking.guest_session_id = None

    db.commit()
    return ClaimGuestBookingResponse(claimed_count=len(guest_bookings))


def _create_booking_confirmation_notification(
    booking: Booking,
    payload: CreateBookingFromPaymentRequest,
) -> None:
    target_url = f"{settings.notification_service_base_url}/api/notifications/booking-confirmations"
    notification_payload = {
        "booking_reference": booking.booking_reference,
        "booking_id": str(booking.id),
        "user_id": booking.user_id,
        "guest_session_id": booking.guest_session_id,
        "channel": "email",
        "recipient_email": payload.customer_email,
        "recipient_phone": payload.customer_phone,
        "locale": "tr-TR",
        "currency": booking.currency,
        "total_amount": float(booking.total_amount),
        "booking_url": f"/bookings/{booking.booking_reference}",
        "trip_summary": f"{payload.items[0].title} • {payload.travelers[0].first_name} {payload.travelers[0].last_name}"
        if payload.items and payload.travelers
        else booking.booking_reference,
    }

    try:
        with httpx.Client(timeout=5.0) as client:
            response = client.post(target_url, json=notification_payload)
            response.raise_for_status()
    except Exception as exc:
        logger.warning(
            "booking_confirmation_notification_failed",
            extra={
                "booking_reference": booking.booking_reference,
                "user_id": booking.user_id,
                "guest_session_id": booking.guest_session_id,
                "error": str(exc),
            },
        )
