from pydantic import BaseModel, Field


class FlightCartOffer(BaseModel):
    class FareOption(BaseModel):
        id: str
        label: str
        badge: str | None = None
        price_delta: float
        hand_baggage: str
        checked_baggage: str
        features: list[str]
        seat_selection: bool = False
        refundable: bool = False
        exchangeable: bool = False
        meal_included: bool = False

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
    selected_fare_option_id: str
    fare_options: list[FareOption]


class AddFlightToCartRequest(BaseModel):
    offer: FlightCartOffer
    guest_session_id: str | None = Field(default=None, max_length=100)


class ClaimGuestCartRequest(BaseModel):
    guest_session_id: str = Field(min_length=1, max_length=100)
    user_id: str = Field(min_length=1, max_length=100)


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


class ClaimGuestCartResponse(BaseModel):
    cart_id: str
    user_id: str | None
    guest_session_id: str | None
    status: str
    currency: str
    items: list[CartItemResponse]
    total_amount: float
