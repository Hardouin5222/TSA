from datetime import datetime, timedelta
from uuid import uuid4

from app.schemas import FlightOffer, FlightSearchRequest, FlightSearchResponse


def search_flights(payload: FlightSearchRequest) -> FlightSearchResponse:
    base_departure = datetime.fromisoformat(f"{payload.departure_date}T07:30:00")
    search_id = str(uuid4())
    normalized_origin = payload.origin.strip().upper()
    normalized_destination = payload.destination.strip().upper()

    providers = [
        ("Duffel", "TK", "Turkish Airlines", 185.0, 0, "8 kg kabin + 20 kg bagaj"),
        ("Travelfusion", "PC", "Pegasus", 121.0, 0, "8 kg kabin"),
        ("Mystifly", "AJ", "AJet", 138.0, 1, "8 kg kabin + 15 kg bagaj"),
    ]

    offers: list[FlightOffer] = []
    for index, (provider, code, airline, base_price, stop_count, baggage) in enumerate(providers):
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
                price_amount=round(dynamic_price, 2),
                price_currency="EUR",
                tags=tags,
            )
        )

    return FlightSearchResponse(
        search_id=search_id,
        route_label=f"{normalized_origin} → {normalized_destination}",
        offers=offers,
    )
