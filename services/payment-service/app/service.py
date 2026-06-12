from uuid import uuid4

from fastapi import HTTPException, status
from sqlalchemy import select
from sqlalchemy.orm import Session

from app.core.settings import get_payment_service_settings
from app.models import PaymentIntent
from app.schemas import (
    CreatePaymentIntentRequest,
    PaymentIntentDetailResponse,
    PaymentIntentResponse,
)

settings = get_payment_service_settings()


def create_payment_intent(payload: CreatePaymentIntentRequest, db: Session) -> PaymentIntentResponse:
    provider_reference = f"{settings.payment_provider}_{uuid4().hex[:16]}"
    payment_intent = PaymentIntent(
        cart_id=payload.cart_id,
        user_id=payload.user_id,
        guest_session_id=payload.guest_session_id,
        provider=settings.payment_provider,
        status="pending",
        amount=payload.total_amount,
        currency=payload.currency,
        item_snapshot={
            "items": [item.model_dump() for item in payload.items],
            "contact": payload.contact.model_dump(),
            "travelers": [traveler.model_dump() for traveler in payload.travelers],
        },
        provider_reference=provider_reference,
        checkout_url=f"/checkout/mock/{provider_reference}",
    )
    db.add(payment_intent)
    db.commit()
    db.refresh(payment_intent)

    return PaymentIntentResponse(
        payment_intent_id=str(payment_intent.id),
        provider=payment_intent.provider,
        provider_reference=payment_intent.provider_reference,
        status=payment_intent.status,
        amount=float(payment_intent.amount),
        currency=payment_intent.currency,
        checkout_url=payment_intent.checkout_url,
    )


def get_payment_intent(provider_reference: str, db: Session) -> PaymentIntentDetailResponse:
    payment_intent = db.scalar(
        select(PaymentIntent).where(PaymentIntent.provider_reference == provider_reference)
    )
    if not payment_intent:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Payment intent not found")

    items = payment_intent.item_snapshot.get("items", [])
    contact = payment_intent.item_snapshot.get("contact", {})
    travelers = payment_intent.item_snapshot.get("travelers", [])
    return PaymentIntentDetailResponse(
        payment_intent_id=str(payment_intent.id),
        provider=payment_intent.provider,
        provider_reference=payment_intent.provider_reference,
        status=payment_intent.status,
        amount=float(payment_intent.amount),
        currency=payment_intent.currency,
        checkout_url=payment_intent.checkout_url,
        cart_id=payment_intent.cart_id,
        user_id=payment_intent.user_id,
        guest_session_id=payment_intent.guest_session_id,
        items=items,
        contact=contact,
        travelers=travelers,
    )


def confirm_payment_intent(provider_reference: str, db: Session) -> PaymentIntentDetailResponse:
    payment_intent = db.scalar(
        select(PaymentIntent).where(PaymentIntent.provider_reference == provider_reference)
    )
    if not payment_intent:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Payment intent not found")

    payment_intent.status = "paid"
    db.commit()
    db.refresh(payment_intent)
    return get_payment_intent(provider_reference, db)
