import json
from datetime import date
from functools import lru_cache
from pathlib import Path
from uuid import uuid4

from app.core.settings import get_car_rental_service_settings
from app.schemas import CarRentalOffer, CarRentalSearchRequest, CarRentalSearchResponse

settings = get_car_rental_service_settings()


def search_cars(payload: CarRentalSearchRequest) -> CarRentalSearchResponse:
    if settings.car_data_mode == "mock_supplier":
        return search_cars_from_mock_supplier(payload)
    return search_cars_synthetic(payload)


def search_cars_from_mock_supplier(payload: CarRentalSearchRequest) -> CarRentalSearchResponse:
    catalog = load_car_catalog()
    location_query = payload.pickup_location.strip().lower()
    rental_days = calculate_rental_days(payload.pickup_date, payload.dropoff_date)

    matching_offers = [
        normalize_car_offer(item, rental_days)
        for item in catalog["offers"]
        if location_query in item["pickup_location"].lower() or location_query in item["search_aliases"]
    ]

    if not matching_offers:
        matching_offers = search_cars_synthetic(payload).offers

    return CarRentalSearchResponse(
        search_id=str(uuid4()),
        route_label=f"{payload.pickup_location.title()} teslim",
        rental_days=rental_days,
        offers=sorted(matching_offers, key=lambda offer: offer.total_price),
    )


def search_cars_synthetic(payload: CarRentalSearchRequest) -> CarRentalSearchResponse:
    rental_days = calculate_rental_days(payload.pickup_date, payload.dropoff_date)
    location = payload.pickup_location.title()
    base_offers = [
        {
            "id": f"car_{location.lower()}_1",
            "provider": "CarTrawler",
            "vendor_name": "Avis",
            "vehicle_name": "Renault Clio or similar",
            "category": "Economy",
            "transmission": "Automatic",
            "fuel_policy": "Full to full",
            "seats": 5,
            "bags": 2,
            "doors": 4,
            "daily_price": 38,
            "currency": "EUR",
            "pickup_location": location,
            "dropoff_location": location,
            "image_url": "https://images.unsplash.com/photo-1549399542-7e3f8b79c341?auto=format&fit=crop&w=1200&q=80",
            "included": ["Temel sigorta", "Havalimani teslim", "Ucretsiz iptal"],
            "tags": ["Ekonomik", "Otomatik"],
            "air_conditioning": True,
            "unlimited_mileage": True,
        },
        {
            "id": f"car_{location.lower()}_2",
            "provider": "CarTrawler",
            "vendor_name": "Enterprise",
            "vehicle_name": "Peugeot 3008 or similar",
            "category": "SUV",
            "transmission": "Automatic",
            "fuel_policy": "Full to full",
            "seats": 5,
            "bags": 4,
            "doors": 5,
            "daily_price": 64,
            "currency": "EUR",
            "pickup_location": location,
            "dropoff_location": location,
            "image_url": "https://images.unsplash.com/photo-1519641471654-76ce0107ad1b?auto=format&fit=crop&w=1200&q=80",
            "included": ["Temel sigorta", "SUV", "Ucretsiz iptal"],
            "tags": ["SUV", "Aile"],
            "air_conditioning": True,
            "unlimited_mileage": True,
        }
    ]

    offers = [normalize_car_offer(item, rental_days) for item in base_offers]
    return CarRentalSearchResponse(
        search_id=str(uuid4()),
        route_label=f"{location} teslim",
        rental_days=rental_days,
        offers=offers,
    )


def normalize_car_offer(item: dict, rental_days: int) -> CarRentalOffer:
    daily_price = float(item["daily_price"])
    return CarRentalOffer(
        id=item["id"],
        provider=item["provider"],
        vendor_name=item["vendor_name"],
        vehicle_name=item["vehicle_name"],
        category=item["category"],
        transmission=item["transmission"],
        fuel_policy=item["fuel_policy"],
        seats=int(item["seats"]),
        bags=int(item["bags"]),
        doors=int(item["doors"]),
        daily_price=daily_price,
        total_price=round(daily_price * rental_days, 2),
        currency=item["currency"],
        pickup_location=item["pickup_location"],
        dropoff_location=item["dropoff_location"],
        image_url=item["image_url"],
        included=list(item["included"]),
        tags=list(item["tags"]),
        air_conditioning=bool(item["air_conditioning"]),
        unlimited_mileage=bool(item["unlimited_mileage"]),
    )


def calculate_rental_days(pickup_date: str, dropoff_date: str) -> int:
    start = date.fromisoformat(pickup_date)
    end = date.fromisoformat(dropoff_date)
    return max((end - start).days, 1)


@lru_cache(maxsize=1)
def load_car_catalog() -> dict:
    catalog_path = settings.car_mock_catalog_path
    resolved_path = (
        Path(catalog_path)
        if catalog_path
        else Path(__file__).resolve().parent / "mock_data" / "car_supplier_catalog.json"
    )
    return json.loads(resolved_path.read_text(encoding="utf-8"))
