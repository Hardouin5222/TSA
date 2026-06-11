from pydantic import BaseModel, Field


class CheckoutCartItem(BaseModel):
    id: str
    item_type: str
    reference_id: str
    title: str
    quantity: int
    unit_price: float
    currency: str
    item_payload: dict


class CreatePaymentIntentRequest(BaseModel):
    cart_id: str = Field(min_length=1)
    user_id: str | None = None
    guest_session_id: str | None = None
    currency: str
    total_amount: float = Field(gt=0)
    items: list[CheckoutCartItem]


class PaymentIntentResponse(BaseModel):
    payment_intent_id: str
    provider: str
    provider_reference: str
    status: str
    amount: float
    currency: str
    checkout_url: str
