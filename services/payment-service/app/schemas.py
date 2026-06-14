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


class CheckoutContact(BaseModel):
    email: str = Field(min_length=3)
    phone: str = Field(min_length=6)


class CheckoutTraveler(BaseModel):
    traveler_type: str = Field(default="adult", min_length=1)
    first_name: str = Field(min_length=1)
    last_name: str = Field(min_length=1)
    birth_date: str = Field(min_length=8)


class CheckoutSpecialRequests(BaseModel):
    seat_preference: str | None = None
    meal_preference: str | None = None
    accessibility_note: str | None = None


class CheckoutBillingDetails(BaseModel):
    invoice_type: str = Field(default="individual", min_length=1)
    full_name: str = Field(min_length=1)
    country: str = Field(min_length=1)
    city: str = Field(min_length=1)
    address_line: str = Field(min_length=1)
    company_name: str | None = None
    tax_number: str | None = None


class CreatePaymentIntentRequest(BaseModel):
    cart_id: str = Field(min_length=1)
    user_id: str | None = None
    guest_session_id: str | None = None
    currency: str
    total_amount: float = Field(gt=0)
    items: list[CheckoutCartItem]
    contact: CheckoutContact
    travelers: list[CheckoutTraveler] = Field(min_length=1)
    special_requests: CheckoutSpecialRequests | None = None
    billing_details: CheckoutBillingDetails


class PaymentIntentResponse(BaseModel):
    payment_intent_id: str
    provider: str
    provider_reference: str
    status: str
    amount: float
    currency: str
    checkout_url: str


class PaymentIntentDetailResponse(PaymentIntentResponse):
    cart_id: str
    user_id: str | None
    guest_session_id: str | None
    items: list[CheckoutCartItem]
    contact: CheckoutContact
    travelers: list[CheckoutTraveler]
    special_requests: CheckoutSpecialRequests | None = None
    billing_details: CheckoutBillingDetails
