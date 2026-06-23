from __future__ import annotations

from datetime import datetime, timedelta, timezone
from typing import Any, Dict
from uuid import uuid4

from .base import FlightSupplierAdapter


def _clean(value: Any) -> str:
    return str(value or "").strip().lower().replace(" ", "_").replace("-", "_")


def _parse_date(value: Any) -> datetime:
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


def _date_key(value: Any) -> str:
    return _parse_date(value).strftime("%Y%m%d")


def _money(amount: float, currency: str = "USD") -> Dict[str, Any]:
    return {
        "amount": round(float(amount), 2),
        "currency": currency,
        "formatted": f"{currency} {float(amount):,.2f}",
    }


def _fare_option(offer_id: str, code: str, name: str, price: float, currency: str, checked_baggage: str, refundable: bool) -> Dict[str, Any]:
    fare_id = f"{offer_id}_{code}"

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
        "features": ["Cabin baggage", "Online check-in"] if checked_baggage == "0 kg" else ["Checked baggage", "Refundable" if refundable else "Checked baggage", "Change allowed"],
    }


class MockFlightAdapter(FlightSupplierAdapter):
    code = "MOCK_SUPPLIER_ENGINE"

    def search(self, payload: Dict[str, Any]) -> Dict[str, Any]:
        origin = str(payload.get("origin") or "IST").upper().strip()
        destination = str(payload.get("destination") or "LHR").upper().strip()
        departure_date = payload.get("departure_date")

        if origin == destination:
            return {
                "search_id": "search_empty_same_origin_destination",
                "offers": [],
                "meta": {"reason": "origin_destination_same"},
            }

        offers = [
            self._offer(origin, destination, departure_date, "MOCK_DUFFEL", 0, 188.90),
            self._offer(origin, destination, departure_date, "MOCK_MYSTIFLY", 1, 164.40),
        ]

        return {
            "search_id": f"search_{origin.lower()}_{destination.lower()}_{_date_key(departure_date)}",
            "offers": offers,
            "meta": {
                "mode": "mock_supplier_engine",
                "origin": origin,
                "destination": destination,
                "departure_date": departure_date,
                "count": len(offers),
            },
        }

    def quote(self, payload: Dict[str, Any]) -> Dict[str, Any]:
        offer_id = str(payload.get("offer_id") or "")
        selected_fare_id = str(payload.get("selected_fare_id") or "")
        supplier_context = dict(payload.get("supplier_context") or {})

        if not offer_id or not selected_fare_id:
            raise ValueError("offer_id and selected_fare_id are required")

        is_mystifly = "mock_mystifly" in offer_id.lower()
        base_price = 164.40 if is_mystifly else 188.90
        selected_price = round(base_price + 45.00, 2) if selected_fare_id.endswith("_flex") else round(base_price, 2)
        quote_id = f"quote_{offer_id}_{uuid4().hex[:12]}"
        expires = datetime.now(timezone.utc) + timedelta(hours=2)
        supplier_code = supplier_context.get("supplier_code") or ("MOCK_MYSTIFLY" if is_mystifly else "MOCK_DUFFEL")

        return {
            "quote_id": quote_id,
            "quote_uuid": quote_id,
            "offer_id": offer_id,
            "selected_fare_id": selected_fare_id,
            "supplier_code": supplier_code,
            "confirmed_price": {
                "amount": selected_price,
                "currency": "USD",
            },
            "confirmed_total_amount": selected_price,
            "currency": "USD",
            "price_changed": False,
            "expires_at": expires.isoformat(),
            "booking_requirements": {
                "contact": ["email", "phone"],
                "travellers": [
                    "first_name",
                    "last_name",
                    "birth_date",
                    "gender",
                    "nationality",
                    "passport_number",
                    "passport_expiry",
                ],
                "billing": ["invoice_type"],
            },
            "checkout_fields": {
                "passport_required": True,
                "birth_date_required": True,
                "gender_required": True,
                "nationality_required": True,
            },
            "rules": {
                "refund": "Refund depends on selected fare package.",
                "change": "Change fees may apply.",
            },
            "supplier_context": {
                **supplier_context,
                "quote_reference": quote_id,
                "expires_at": expires.isoformat(),
            },
            "status": "quoted",
        }

    def book(self, payload: Dict[str, Any]) -> Dict[str, Any]:
        quote_id = str(payload.get("quote_id") or "")
        if not quote_id:
            raise ValueError("quote_id is required")

        raw_booking_seed = str(payload.get("booking_reference") or quote_id).upper().replace("_", "-").strip("-")
        booking_seed = raw_booking_seed[-10:].strip("-") or "MOCK-0001"

        return {
            "booking_status": "confirmed",
            "supplier_booking_reference": f"SBR-{booking_seed}",
            "pnr": f"PNR{booking_seed[-6:]}",
            "ticket_numbers": [f"235-{booking_seed[-10:]}"],
            "fulfillment_status": "ticket_issued",
            "manual_action_required": False,
            "supplier_context": payload.get("supplier_context") or {},
        }

    def status(self, reference: str) -> Dict[str, Any]:
        clean_reference = str(reference or "").strip().upper()
        if not clean_reference:
            raise ValueError("reference is required")

        booking_seed = clean_reference[4:] if clean_reference.startswith("SBR-") else clean_reference
        booking_seed = booking_seed.replace("_", "-")

        return {
            "booking_status": "confirmed",
            "fulfillment_status": "ticket_issued",
            "supplier_booking_reference": clean_reference,
            "pnr": f"PNR{booking_seed[-6:]}",
            "ticket_numbers": [f"235-{booking_seed[-10:]}"],
            "manual_action_required": False,
            "supplier_context": {
                "supplier_code": self.code,
                "status_mode": "mock_status",
            },
        }

    def _offer(self, origin: str, destination: str, departure_date: Any, supplier: str, index: int, base_price: float) -> Dict[str, Any]:
        currency = "USD"
        dep = _parse_date(departure_date) + timedelta(hours=index * 2)
        arr = dep + timedelta(hours=3, minutes=45 + index * 15)

        offer_id = f"{_clean(supplier)}_{_clean(origin)}_{_clean(destination)}_{_date_key(departure_date)}"
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
