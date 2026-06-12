from datetime import datetime, timedelta
from uuid import uuid4

from app.schemas import FlightOffer, FlightSearchRequest, FlightSearchResponse


def search_flights(payload: FlightSearchRequest) -> FlightSearchResponse:
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
