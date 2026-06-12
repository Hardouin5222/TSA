import json
from datetime import date
from functools import lru_cache
from pathlib import Path
from uuid import uuid4

from app.core.settings import get_hotel_service_settings
from app.schemas import HotelOffer, HotelSearchRequest, HotelSearchResponse

settings = get_hotel_service_settings()


def search_hotels(payload: HotelSearchRequest) -> HotelSearchResponse:
    if settings.hotel_data_mode == "mock_supplier":
        return search_hotels_from_mock_supplier(payload)
    return search_hotels_synthetic(payload)


def search_hotels_from_mock_supplier(payload: HotelSearchRequest) -> HotelSearchResponse:
    catalog = load_hotel_catalog()
    city_query = payload.city.strip().lower()
    nights = calculate_nights(payload.check_in, payload.check_out)

    matching_offers = [
        normalize_hotel_offer(item, nights)
        for item in catalog["offers"]
        if city_query in item["city"].lower() or city_query in item["search_aliases"]
    ]

    if not matching_offers:
        matching_offers = search_hotels_synthetic(payload).offers

    return HotelSearchResponse(
        search_id=str(uuid4()),
        destination_label=payload.city.title(),
        nights=nights,
        offers=sorted(matching_offers, key=lambda offer: offer.total_price),
    )


def search_hotels_synthetic(payload: HotelSearchRequest) -> HotelSearchResponse:
    nights = calculate_nights(payload.check_in, payload.check_out)
    city = payload.city.title()
    base_offers = [
        {
            "id": f"htl_{city.lower()}_1",
            "provider": "Hotelbeds",
            "property_id": f"{city[:3].upper()}-RES-01",
            "name": f"{city} Marina Hotel",
            "city": city,
            "country_code": "TR",
            "star_rating": 4.0,
            "guest_score": 8.6,
            "guest_count": 1248,
            "nightly_price": 109.0,
            "currency": "EUR",
            "board_type": "Oda kahvalti",
            "cancellation_policy": "Ucretsiz iptal",
            "neighborhood": "Merkez",
            "image_url": "https://images.unsplash.com/photo-1566073771259-6a8506099945?auto=format&fit=crop&w=1200&q=80",
            "amenities": ["Wi-Fi", "Kahvalti", "Havuz", "Aile dostu"],
            "tags": ["Merkezi", "Ucretsiz iptal", "Aile dostu"],
            "room_name": "Deluxe Room",
            "room_size_sqm": 28,
            "refundable": True,
            "pay_at_hotel": False,
            "latitude": 36.8841,
            "longitude": 30.7056,
        },
        {
            "id": f"htl_{city.lower()}_2",
            "provider": "Expedia Rapid",
            "property_id": f"{city[:3].upper()}-BIZ-02",
            "name": f"{city} Business Suites",
            "city": city,
            "country_code": "TR",
            "star_rating": 4.5,
            "guest_score": 9.0,
            "guest_count": 864,
            "nightly_price": 142.0,
            "currency": "EUR",
            "board_type": "Sadece oda",
            "cancellation_policy": "Kismi iade",
            "neighborhood": "Is ve alisveris bolgesi",
            "image_url": "https://images.unsplash.com/photo-1551882547-ff40c63fe5fa?auto=format&fit=crop&w=1200&q=80",
            "amenities": ["Wi-Fi", "Spa", "Gym", "Airport shuttle"],
            "tags": ["Is seyahati", "Premium", "Esnek"],
            "room_name": "Executive Suite",
            "room_size_sqm": 34,
            "refundable": True,
            "pay_at_hotel": True,
            "latitude": 41.0082,
            "longitude": 28.9784,
        },
    ]

    offers = [normalize_hotel_offer(item, nights) for item in base_offers]
    return HotelSearchResponse(
        search_id=str(uuid4()),
        destination_label=city,
        nights=nights,
        offers=offers,
    )


def normalize_hotel_offer(item: dict, nights: int) -> HotelOffer:
    nightly_price = float(item["nightly_price"])
    return HotelOffer(
        id=item["id"],
        provider=item["provider"],
        property_id=item["property_id"],
        name=item["name"],
        city=item["city"],
        country_code=item["country_code"],
        star_rating=float(item["star_rating"]),
        guest_score=float(item["guest_score"]),
        guest_count=int(item["guest_count"]),
        nightly_price=nightly_price,
        total_price=round(nightly_price * nights, 2),
        currency=item["currency"],
        board_type=item["board_type"],
        cancellation_policy=item["cancellation_policy"],
        neighborhood=item["neighborhood"],
        image_url=item["image_url"],
        amenities=list(item["amenities"]),
        tags=list(item["tags"]),
        room_name=item["room_name"],
        room_size_sqm=int(item["room_size_sqm"]),
        refundable=bool(item["refundable"]),
        pay_at_hotel=bool(item["pay_at_hotel"]),
        latitude=float(item["latitude"]),
        longitude=float(item["longitude"]),
    )


def calculate_nights(check_in: str, check_out: str) -> int:
    start = date.fromisoformat(check_in)
    end = date.fromisoformat(check_out)
    return max((end - start).days, 1)


@lru_cache(maxsize=1)
def load_hotel_catalog() -> dict:
    catalog_path = settings.hotel_mock_catalog_path
    resolved_path = (
        Path(catalog_path)
        if catalog_path
        else Path(__file__).resolve().parent / "mock_data" / "hotel_supplier_catalog.json"
    )
    return json.loads(resolved_path.read_text(encoding="utf-8"))
