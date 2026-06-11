from pydantic import BaseModel, Field


class FlightCartOffer(BaseModel):
    id: str
    provider: str
    airline_name: str
    airline_code: str
    origin: str
    destination: str
    departure_at: str
    arrival_at: str
    duration_minutes: int
    stop_count: int
    cabin_class: str
    baggage_summary: str
    fare_family: str
    cancellation_policy: str
    seat_pitch: str
    package_score: int
    price_amount: float
    price_currency: str
    tags: list[str]


class AddFlightToCartRequest(BaseModel):
    offer: FlightCartOffer
    guest_session_id: str | None = Field(default=None, max_length=100)


class CartItemResponse(BaseModel):
    id: str
    item_type: str
    reference_id: str
    title: str
    quantity: int
    unit_price: float
    currency: str
    item_payload: dict


class CartResponse(BaseModel):
    cart_id: str
    user_id: str | None
    guest_session_id: str | None
    status: str
    currency: str
    items: list[CartItemResponse]
    total_amount: float
