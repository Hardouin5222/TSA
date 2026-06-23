from __future__ import annotations

from datetime import datetime, timedelta, timezone
from typing import Any, Dict, List, Optional
from uuid import uuid4

from fastapi import FastAPI, HTTPException
from fastapi.middleware.cors import CORSMiddleware
from pydantic import BaseModel, Field

from app.adapters import get_flight_adapter

app = FastAPI(title="TSA Supplier Engine", version="0.1.3")

app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)


class FlightSearchRequest(BaseModel):
    origin: Optional[str] = None
    destination: Optional[str] = None
    departure_date: Optional[str] = None
    return_date: Optional[str] = None
    adult_count: int = 1
    child_count: int = 0
    infant_count: int = 0
    cabin_class: Optional[str] = "economy"
    currency: Optional[str] = "USD"


class FlightQuoteRequest(BaseModel):
    offer_id: str
    selected_fare_id: str
    passenger_summary: Dict[str, Any] = Field(default_factory=dict)
    supplier_context: Dict[str, Any] = Field(default_factory=dict)


class FlightBookRequest(BaseModel):
    quote_id: str
    booking_reference: Optional[str] = None
    payment_reference: Optional[str] = None
    contact: Dict[str, Any] = Field(default_factory=dict)
    travellers: List[Dict[str, Any]] = Field(default_factory=list)
    selected_fare_id: Optional[str] = None
    supplier_context: Dict[str, Any] = Field(default_factory=dict)



def _payload_dict(payload: BaseModel) -> Dict[str, Any]:
    if hasattr(payload, "model_dump"):
        return payload.model_dump()

    return payload.dict()

def _parse_date(value: Optional[str]) -> datetime:
    if not value:
        return (datetime.now(timezone.utc) + timedelta(days=14)).replace(hour=9, minute=35, second=0, microsecond=0)

    raw_value = str(value).split(" ")[0].strip()
    for fmt in ("%Y-%m-%d", "%d/%m/%Y", "%m/%d/%Y"):
        try:
            d = datetime.strptime(raw_value, fmt)
            return d.replace(tzinfo=timezone.utc, hour=9, minute=35, second=0, microsecond=0)
        except ValueError:
            continue

    return (datetime.now(timezone.utc) + timedelta(days=14)).replace(hour=9, minute=35, second=0, microsecond=0)


def _normalized_departure_date(value: Optional[str]) -> str:
    return _parse_date(value).strftime("%Y%m%d")


def _clean(value: str) -> str:
    return str(value or "").strip().lower().replace(" ", "_").replace("-", "_")


def _stable_offer_id(supplier: str, origin: str, destination: str, departure_date: Optional[str]) -> str:
    """Return the same offer id for the same mock search.

    This is critical for the Laravel Booking Core bridge. The selected offer id
    must remain stable between search and quote, otherwise quote validation will
    think the offer disappeared.
    """
    return f"{_clean(supplier)}_{_clean(origin)}_{_clean(destination)}_{_normalized_departure_date(departure_date)}"


def _money(amount: float, currency: str = "USD") -> Dict[str, Any]:
    return {
        "amount": round(float(amount), 2),
        "currency": currency,
        "formatted": f"{currency} {float(amount):,.2f}",
    }


def _fare_option(offer_id: str, code: str, name: str, price: float, currency: str, checked_baggage: str, refundable: bool) -> Dict[str, Any]:
    fare_id = f"{offer_id}_{code}"
    features = ["Cabin baggage", "Online check-in"]
    if checked_baggage != "0 kg":
        features = ["Checked baggage", "Refundable" if refundable else "Checked baggage", "Change allowed"]

    return {
        "id": fare_id,
        "fare_id": fare_id,
        "code": code,
        "name": name,
        "title": name,
        "price": round(price, 2),
        "total_amount": round(price, 2),
        "amount": round(price, 2),
        "currency": currency,
        "display_price": f"{currency} {price:,.2f}",
        "money": _money(price, currency),
        "checked_baggage": checked_baggage,
        "cabin_baggage": "8 kg",
        "refundable": refundable,
        "exchangeable": True,
        "features": features,
    }


def _price_for_fare(offer_id: str, selected_fare_id: str) -> float:
    if offer_id.startswith("mock_mystifly"):
        base_price = 164.40
    else:
        base_price = 188.90

    if selected_fare_id.endswith("_flex"):
        return round(base_price + 45.00, 2)

    return round(base_price, 2)


def _supplier_from_offer_id(offer_id: str) -> str:
    if offer_id.startswith("mock_mystifly"):
        return "MOCK_MYSTIFLY"
    if offer_id.startswith("mock_duffel"):
        return "MOCK_DUFFEL"
    return "MOCK_SUPPLIER"


def _offer(origin: str, destination: str, departure_date: Optional[str], supplier: str, index: int, base_price: float) -> Dict[str, Any]:
    currency = "USD"
    dep = _parse_date(departure_date) + timedelta(hours=index * 2)
    arr = dep + timedelta(hours=3, minutes=45 + index * 15)

    offer_id = _stable_offer_id(supplier, origin, destination, departure_date)
    expires = datetime.now(timezone.utc) + timedelta(minutes=20)
    supplier_offer_id = f"RAW-{supplier}-{offer_id.upper()}"

    airline_code = "TK" if index == 0 else "PC"
    airline_name = "Turkish Airlines" if index == 0 else "Pegasus Airlines"
    stop_count = 0 if index == 0 else 1
    duration_minutes = int((arr - dep).total_seconds() / 60)

    fare_options = [
        _fare_option(offer_id, "light", "Eco Light", base_price, currency, "0 kg", False),
        _fare_option(offer_id, "flex", "Eco Flex", base_price + 45.00, currency, "20 kg", True),
    ]

    supplier_context = {
        "supplier_code": supplier,
        "raw_offer_id": supplier_offer_id,
        "pricing_token": f"PT-{offer_id}",
        "session_id": f"SESSION-{offer_id}",
        "quote_reference": None,
        "expires_at": expires.isoformat(),
    }

    return {
        "offer_id": offer_id,
        "id": offer_id,
        "supplier": supplier,
        "supplier_code": supplier,
        "supplier_offer_id": supplier_offer_id,
        "origin": origin,
        "destination": destination,
        "departure_at": dep.isoformat(),
        "arrival_at": arr.isoformat(),
        "duration_minutes": duration_minutes,
        "duration_label": f"{duration_minutes // 60}sa {duration_minutes % 60}dk" if duration_minutes % 60 else f"{duration_minutes // 60}sa",
        "stop_count": stop_count,
        "stop_label": "Direkt" if stop_count == 0 else f"{stop_count} aktarma",
        "airline": {
            "code": airline_code,
            "name": airline_name,
        },
        "airline_code": airline_code,
        "airline_name": airline_name,
        "segments": [
            {
                "origin": origin,
                "destination": destination,
                "departure_at": dep.isoformat(),
                "arrival_at": arr.isoformat(),
                "airline_code": airline_code,
                "airline_name": airline_name,
                "flight_number": f"{900 + index}",
                "duration_minutes": duration_minutes,
            }
        ],
        "fare_options": fare_options,
        "selected_fare": fare_options[0],
        "selected_fare_id": fare_options[0]["id"],
        "baggage": {
            "cabin": "8 kg",
            "checked": "0 kg",
        },
        "price": round(base_price, 2),
        "total_amount": round(base_price, 2),
        "amount": round(base_price, 2),
        "display_price": f"{currency} {base_price:,.2f}",
        "money": _money(base_price, currency),
        "taxes": round(base_price * 0.18, 2),
        "currency": currency,
        "rules": {
            "refund": "Refund depends on selected fare package.",
            "change": "Change fees may apply.",
        },
        "capabilities": {
            "branded_fares_supported": True,
            "checked_baggage_supported": True,
            "seat_selection_supported": False,
            "meal_selection_supported": False,
            "refundable": False,
            "exchangeable": True,
            "hold_supported": False,
            "instant_ticketing_supported": True,
            "passport_required": True,
            "birth_date_required": True,
            "gender_required": True,
            "nationality_required": True,
        },
        "expires_at": expires.isoformat(),
        "supplier_context": supplier_context,
    }


@app.get("/health")
def health() -> Dict[str, str]:
    return {"status": "ok", "service": "tsa-supplier-engine"}


@app.get("/api/health")
def api_health() -> Dict[str, str]:
    return health()


@app.post("/api/flights/search")
def search_flights(payload: FlightSearchRequest) -> Dict[str, Any]:
    adapter = get_flight_adapter()

    return adapter.search(_payload_dict(payload))

@app.post("/api/flights/quote")
def quote_flight(payload: FlightQuoteRequest) -> Dict[str, Any]:
    adapter = get_flight_adapter()

    try:
        return adapter.quote(_payload_dict(payload))
    except ValueError as exc:
        raise HTTPException(status_code=422, detail=str(exc)) from exc

@app.post("/api/flights/book")
def book_flight(payload: FlightBookRequest) -> Dict[str, Any]:
    adapter = get_flight_adapter()

    try:
        return adapter.book(_payload_dict(payload))
    except ValueError as exc:
        raise HTTPException(status_code=422, detail=str(exc)) from exc

@app.get("/api/flights/bookings/{reference}/status")
def flight_booking_status(reference: str) -> Dict[str, Any]:
    adapter = get_flight_adapter()

    try:
        return adapter.status(reference)
    except ValueError as exc:
        raise HTTPException(status_code=422, detail=str(exc)) from exc
