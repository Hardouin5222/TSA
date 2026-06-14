from pydantic import BaseModel, Field
from travel_shared import FareServiceFlags, SupplierContext, SupplierOfferCapabilities


class FlightSearchRequest(BaseModel):
    origin: str = Field(min_length=3, max_length=64)
    destination: str = Field(min_length=3, max_length=64)
    departure_date: str
    return_date: str | None = None
    adult_count: int = Field(default=1, ge=1, le=9)
    cabin_class: str = Field(default="economy")


class FlightOffer(BaseModel):
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
        service_flags: FareServiceFlags = Field(default_factory=FareServiceFlags)

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
    capabilities: SupplierOfferCapabilities = Field(default_factory=SupplierOfferCapabilities)
    supplier_context: SupplierContext = Field(default_factory=SupplierContext)


class FlightSearchResponse(BaseModel):
    search_id: str
    route_label: str
    offers: list[FlightOffer]
