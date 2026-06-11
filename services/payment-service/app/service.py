from uuid import uuid4

from sqlalchemy.orm import Session

from app.core.settings import get_payment_service_settings
from app.models import PaymentIntent
from app.schemas import CreatePaymentIntentRequest, PaymentIntentResponse

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
        item_snapshot={"items": [item.model_dump() for item in payload.items]},
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
