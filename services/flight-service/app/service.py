import json
from datetime import datetime, timedelta
from functools import lru_cache
from pathlib import Path
from uuid import uuid4

from app.core.settings import get_flight_service_settings
from app.schemas import FlightOffer, FlightSearchRequest, FlightSearchResponse

settings = get_flight_service_settings()


def search_flights(payload: FlightSearchRequest) -> FlightSearchResponse:
    if settings.flight_data_mode.lower() == "mock_supplier":
        return search_flights_from_mock_supplier(payload)
    return search_flights_synthetic(payload)


@lru_cache
def load_mock_catalog() -> dict:
    configured_path = settings.flight_mock_catalog_path
    if configured_path:
        candidate = Path(configured_path)
        catalog_path = candidate if candidate.is_absolute() else Path(__file__).parent / candidate
    else:
        catalog_path = Path(__file__).parent / "mock_data" / "flight_supplier_catalog.json"

    with catalog_path.open("r", encoding="utf-8") as handle:
        return json.load(handle)


def search_flights_from_mock_supplier(payload: FlightSearchRequest) -> FlightSearchResponse:
    normalized_origin = payload.origin.strip().upper()
    normalized_destination = payload.destination.strip().upper()
    search_id = str(uuid4())

    catalog = load_mock_catalog()
    raw_offers = [
        offer
        for offer in catalog.get("offers", [])
        if offer.get("origin") == normalized_origin and offer.get("destination") == normalized_destination
    ]

    if not raw_offers:
        raw_offers = build_fallback_supplier_templates(normalized_origin, normalized_destination)

    offers = [
        build_offer_from_supplier_template(
            template=template,
            payload=payload,
            index=index,
            normalized_origin=normalized_origin,
            normalized_destination=normalized_destination,
        )
        for index, template in enumerate(raw_offers)
    ]

    return FlightSearchResponse(
        search_id=search_id,
        route_label=f"{normalized_origin} -> {normalized_destination}",
        offers=offers,
    )


def build_offer_from_supplier_template(
    *,
    template: dict,
    payload: FlightSearchRequest,
    index: int,
    normalized_origin: str,
    normalized_destination: str,
) -> FlightOffer:
    departure_time = datetime.fromisoformat(f"{payload.departure_date}T{template['base_departure_time']}:00")
    duration_minutes = int(template["duration_minutes"])
    arrival_time = departure_time + timedelta(minutes=duration_minutes)
    extra_adult_delta = 43 + (index * 4)
    base_price = float(template["base_price"])
    passenger_total = base_price + max(payload.adult_count - 1, 0) * extra_adult_delta

    if payload.cabin_class.lower() == "business":
        passenger_total *= 2.15

    raw_fare_options = template.get("fare_options", [])
    selected_fare_option_id = raw_fare_options[0]["id"] if raw_fare_options else "default"

    return FlightOffer(
        id=f"flt_{index + 1}",
        provider=str(template["provider"]),
        airline_name=str(template["airline_name"]),
        airline_code=str(template["airline_code"]),
        origin=normalized_origin,
        destination=normalized_destination,
        departure_at=departure_time.isoformat(),
        arrival_at=arrival_time.isoformat(),
        duration_minutes=duration_minutes,
        stop_count=int(template["stop_count"]),
        cabin_class=payload.cabin_class,
        baggage_summary=str(template["baggage_summary"]),
        fare_family=str(template["fare_family"]),
        cancellation_policy=str(template["cancellation_policy"]),
        seat_pitch=str(template["seat_pitch"]),
        package_score=int(template["package_score"]),
        price_amount=round(passenger_total, 2),
        price_currency=str(template.get("price_currency", "EUR")),
        tags=build_tags(template),
        selected_fare_option_id=selected_fare_option_id,
        fare_options=raw_fare_options,
    )


def build_tags(template: dict) -> list[str]:
    tags = ["Hizli checkout", "Paket icin uygun"]
    if int(template["stop_count"]) == 0:
        tags.insert(0, "Direkt ucus")
    else:
        tags.insert(0, "Aktarmali secenek")
    return tags


def build_fallback_supplier_templates(origin: str, destination: str) -> list[dict]:
    return [
        {
            "origin": origin,
            "destination": destination,
            "provider": "Duffel",
            "airline_code": "TK",
            "airline_name": "Turkish Airlines",
            "base_departure_time": "08:20",
            "duration_minutes": 95,
            "stop_count": 0,
            "base_price": 189.0,
            "price_currency": "EUR",
            "baggage_summary": "8 kg kabin + 20 kg bagaj",
            "fare_family": "Eco Fly",
            "cancellation_policy": "Temel iade kurali",
            "seat_pitch": "78 cm",
            "package_score": 89,
            "fare_options": [
                {
                    "id": "eco",
                    "label": "Eco Fly",
                    "badge": None,
                    "price_delta": 0.0,
                    "hand_baggage": "8 kg kabin",
                    "checked_baggage": "20 kg bagaj",
                    "features": ["Bagaj dahil", "Temel paket"],
                    "seat_selection": False,
                    "refundable": False,
                    "exchangeable": False,
                    "meal_included": False,
                },
                {
                    "id": "extra",
                    "label": "Extra Fly",
                    "badge": "Onerilen",
                    "price_delta": 32.0,
                    "hand_baggage": "8 kg kabin",
                    "checked_baggage": "25 kg bagaj",
                    "features": ["Koltuk secimi", "Degisiklik hakki"],
                    "seat_selection": True,
                    "refundable": True,
                    "exchangeable": True,
                    "meal_included": False,
                },
            ],
        },
        {
            "origin": origin,
            "destination": destination,
            "provider": "Travelfusion",
            "airline_code": "PC",
            "airline_name": "Pegasus",
            "base_departure_time": "12:10",
            "duration_minutes": 110,
            "stop_count": 0,
            "base_price": 159.0,
            "price_currency": "EUR",
            "baggage_summary": "8 kg kabin",
            "fare_family": "Light",
            "cancellation_policy": "Degisiklik ucretli",
            "seat_pitch": "76 cm",
            "package_score": 83,
            "fare_options": [
                {
                    "id": "light",
                    "label": "Light",
                    "badge": None,
                    "price_delta": 0.0,
                    "hand_baggage": "8 kg kabin",
                    "checked_baggage": "Bagaj yok",
                    "features": ["Temel paket", "Dusuk fiyat"],
                    "seat_selection": False,
                    "refundable": False,
                    "exchangeable": False,
                    "meal_included": False,
                },
                {
                    "id": "saver",
                    "label": "Saver",
                    "badge": "Onerilen",
                    "price_delta": 21.0,
                    "hand_baggage": "8 kg kabin",
                    "checked_baggage": "15 kg bagaj",
                    "features": ["Bir valiz", "Koltuk secimi"],
                    "seat_selection": True,
                    "refundable": False,
                    "exchangeable": True,
                    "meal_included": False,
                },
            ],
        },
        {
            "origin": origin,
            "destination": destination,
            "provider": "Mystifly",
            "airline_code": "AJ",
            "airline_name": "AJet",
            "base_departure_time": "18:45",
            "duration_minutes": 170,
            "stop_count": 1,
            "base_price": 172.0,
            "price_currency": "EUR",
            "baggage_summary": "8 kg kabin + 15 kg bagaj",
            "fare_family": "Value",
            "cancellation_policy": "Kismi iade kosullu",
            "seat_pitch": "78 cm",
            "package_score": 84,
            "fare_options": [
                {
                    "id": "value",
                    "label": "Value",
                    "badge": None,
                    "price_delta": 0.0,
                    "hand_baggage": "8 kg kabin",
                    "checked_baggage": "15 kg bagaj",
                    "features": ["Temel paket", "Ekonomik secenek"],
                    "seat_selection": False,
                    "refundable": False,
                    "exchangeable": False,
                    "meal_included": False,
                },
                {
                    "id": "prime",
                    "label": "Prime",
                    "badge": "Onerilen",
                    "price_delta": 34.0,
                    "hand_baggage": "10 kg kabin",
                    "checked_baggage": "20 kg bagaj",
                    "features": ["Koltuk secimi", "Esnek degisiklik"],
                    "seat_selection": True,
                    "refundable": True,
                    "exchangeable": True,
                    "meal_included": False,
                },
            ],
        },
    ]


def search_flights_synthetic(payload: FlightSearchRequest) -> FlightSearchResponse:
    base_departure = datetime.fromisoformat(f"{payload.departure_date}T07:30:00")
    search_id = str(uuid4())
    normalized_origin = payload.origin.strip().upper()
    normalized_destination = payload.destination.strip().upper()

    providers = [
        (
            "Duffel",
            "TK",
            "Turkish Airlines",
            185.0,
            0,
            "8 kg kabin + 20 kg bagaj",
            "Eco Flex",
            "Iadeli farkli kurallar",
            "79 cm",
            92,
            [
                {
                    "id": "eco",
                    "label": "Super eko",
                    "badge": None,
                    "price_delta": 0.0,
                    "hand_baggage": "8 kg kabin",
                    "checked_baggage": "20 kg bagaj",
                    "features": ["Standart koltuk", "Temel iade kurali"],
                    "seat_selection": False,
                    "refundable": False,
                    "exchangeable": False,
                    "meal_included": False,
                },
                {
                    "id": "advantage",
                    "label": "Avantaj",
                    "badge": "Onerilen",
                    "price_delta": 34.0,
                    "hand_baggage": "8 kg kabin",
                    "checked_baggage": "25 kg bagaj",
                    "features": ["Koltuk secimi", "Degisiklik hakki", "Hizli iade"],
                    "seat_selection": True,
                    "refundable": True,
                    "exchangeable": True,
                    "meal_included": False,
                },
                {
                    "id": "comfort",
                    "label": "Comfort flex",
                    "badge": None,
                    "price_delta": 68.0,
                    "hand_baggage": "10 kg kabin",
                    "checked_baggage": "30 kg bagaj",
                    "features": ["Esnek iade", "Koltuk secimi", "Yemek dahil"],
                    "seat_selection": True,
                    "refundable": True,
                    "exchangeable": True,
                    "meal_included": True,
                },
            ],
        ),
        (
            "Travelfusion",
            "PC",
            "Pegasus",
            121.0,
            0,
            "8 kg kabin",
            "Light",
            "Degisiklik ucretli",
            "76 cm",
            84,
            [
                {
                    "id": "light",
                    "label": "Light",
                    "badge": None,
                    "price_delta": 0.0,
                    "hand_baggage": "8 kg kabin",
                    "checked_baggage": "Bagaj yok",
                    "features": ["En dusuk fiyat", "Temel paket"],
                    "seat_selection": False,
                    "refundable": False,
                    "exchangeable": False,
                    "meal_included": False,
                },
                {
                    "id": "saver",
                    "label": "Saver",
                    "badge": "Onerilen",
                    "price_delta": 22.0,
                    "hand_baggage": "8 kg kabin",
                    "checked_baggage": "15 kg bagaj",
                    "features": ["Bir valiz", "Koltuk secimi"],
                    "seat_selection": True,
                    "refundable": False,
                    "exchangeable": True,
                    "meal_included": False,
                },
                {
                    "id": "flex",
                    "label": "Flex",
                    "badge": None,
                    "price_delta": 49.0,
                    "hand_baggage": "8 kg kabin",
                    "checked_baggage": "20 kg bagaj",
                    "features": ["Iade destegi", "Koltuk secimi", "Degisiklik hakki"],
                    "seat_selection": True,
                    "refundable": True,
                    "exchangeable": True,
                    "meal_included": False,
                },
            ],
        ),
        (
            "Mystifly",
            "AJ",
            "AJet",
            138.0,
            1,
            "8 kg kabin + 15 kg bagaj",
            "Value",
            "Kismi iade kosullu",
            "78 cm",
            88,
            [
                {
                    "id": "value",
                    "label": "Value",
                    "badge": None,
                    "price_delta": 0.0,
                    "hand_baggage": "8 kg kabin",
                    "checked_baggage": "15 kg bagaj",
                    "features": ["Temel paket", "Ekonomik secenek"],
                    "seat_selection": False,
                    "refundable": False,
                    "exchangeable": False,
                    "meal_included": False,
                },
                {
                    "id": "plus",
                    "label": "Plus",
                    "badge": "Onerilen",
                    "price_delta": 29.0,
                    "hand_baggage": "8 kg kabin",
                    "checked_baggage": "20 kg bagaj",
                    "features": ["Koltuk secimi", "Ek bagaj", "Hizli degisiklik"],
                    "seat_selection": True,
                    "refundable": False,
                    "exchangeable": True,
                    "meal_included": False,
                },
                {
                    "id": "prime",
                    "label": "Prime",
                    "badge": None,
                    "price_delta": 58.0,
                    "hand_baggage": "10 kg kabin",
                    "checked_baggage": "25 kg bagaj",
                    "features": ["Esnek degisiklik", "Koltuk secimi", "Yemek dahil"],
                    "seat_selection": True,
                    "refundable": True,
                    "exchangeable": True,
                    "meal_included": True,
                },
            ],
        ),
    ]

    offers: list[FlightOffer] = []
    for index, (
        provider,
        code,
        airline,
        base_price,
        stop_count,
        baggage,
        fare_family,
        cancellation_policy,
        seat_pitch,
        package_score,
        fare_options,
    ) in enumerate(providers):
        departure_time = base_departure + timedelta(minutes=index * 165)
        duration_minutes = 75 + (index * 20) + (stop_count * 65)
        arrival_time = departure_time + timedelta(minutes=duration_minutes)
        dynamic_price = base_price + (payload.adult_count - 1) * 47

        tags = ["Hizli checkout", "Paket icin uygun"]
        if stop_count == 0:
            tags.insert(0, "Direkt ucus")
        else:
            tags.insert(0, "Daha ekonomik")

        offers.append(
            FlightOffer(
                id=f"flt_{index + 1}",
                provider=provider,
                airline_name=airline,
                airline_code=code,
                origin=normalized_origin,
                destination=normalized_destination,
                departure_at=departure_time.isoformat(),
                arrival_at=arrival_time.isoformat(),
                duration_minutes=duration_minutes,
                stop_count=stop_count,
                cabin_class=payload.cabin_class,
                baggage_summary=baggage,
                fare_family=fare_family,
                cancellation_policy=cancellation_policy,
                seat_pitch=seat_pitch,
                package_score=package_score,
                price_amount=round(dynamic_price, 2),
                price_currency="EUR",
                tags=tags,
                selected_fare_option_id=fare_options[0]["id"],
                fare_options=fare_options,
            )
        )

    return FlightSearchResponse(
        search_id=search_id,
        route_label=f"{normalized_origin} -> {normalized_destination}",
        offers=offers,
    )
