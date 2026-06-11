from pydantic import BaseModel, Field


class FlightSearchRequest(BaseModel):
    origin: str = Field(min_length=3, max_length=64)
    destination: str = Field(min_length=3, max_length=64)
    departure_date: str
    return_date: str | None = None
    adult_count: int = Field(default=1, ge=1, le=9)
    cabin_class: str = Field(default="economy")


class FlightOffer(BaseModel):
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
    price_amount: float
    price_currency: str
    tags: list[str]


class FlightSearchResponse(BaseModel):
    search_id: str
    route_label: str
    offers: list[FlightOffer]
