from pydantic import BaseModel, Field


class HotelSearchRequest(BaseModel):
    city: str = Field(min_length=2, max_length=128)
    check_in: str
    check_out: str
    adult_count: int = Field(default=2, ge=1, le=9)
    room_count: int = Field(default=1, ge=1, le=5)


class HotelOffer(BaseModel):
    id: str
    provider: str
    property_id: str
    name: str
    city: str
    country_code: str
    star_rating: float
    guest_score: float
    guest_count: int
    nightly_price: float
    total_price: float
    currency: str
    board_type: str
    cancellation_policy: str
    neighborhood: str
    image_url: str
    amenities: list[str]
    tags: list[str]
    room_name: str
    room_size_sqm: int
    refundable: bool
    pay_at_hotel: bool
    latitude: float
    longitude: float


class HotelSearchResponse(BaseModel):
    search_id: str
    destination_label: str
    nights: int
    offers: list[HotelOffer]
