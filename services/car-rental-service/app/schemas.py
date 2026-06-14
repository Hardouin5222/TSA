from pydantic import BaseModel, Field


class CarRentalSearchRequest(BaseModel):
    pickup_location: str = Field(min_length=2, max_length=128)
    pickup_date: str
    dropoff_date: str
    driver_age: int = Field(default=30, ge=18, le=80)


class CarRentalOffer(BaseModel):
    id: str
    provider: str
    vendor_name: str
    vehicle_name: str
    category: str
    transmission: str
    fuel_policy: str
    seats: int
    bags: int
    doors: int
    daily_price: float
    total_price: float
    currency: str
    pickup_location: str
    dropoff_location: str
    image_url: str
    included: list[str]
    tags: list[str]
    air_conditioning: bool
    unlimited_mileage: bool


class CarRentalSearchResponse(BaseModel):
    search_id: str
    route_label: str
    rental_days: int
    offers: list[CarRentalOffer]
