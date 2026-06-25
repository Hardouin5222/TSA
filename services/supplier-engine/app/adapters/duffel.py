from __future__ import annotations

import json
import os
import re
from datetime import datetime, timezone
from typing import Any, Dict, List
from urllib import request as urllib_request
from urllib.error import HTTPError, URLError
from urllib.parse import urlencode
from uuid import uuid4

from .base import FlightSupplierAdapter
from .errors import SupplierAdapterError, SupplierConfigurationError, SupplierValidationError


class DuffelFlightAdapter(FlightSupplierAdapter):
    """Duffel sandbox adapter.

    Safety rules:
    - Requires DUFFEL_API_TOKEN.
    - In sandbox mode, token must start with duffel_test_ unless DUFFEL_ALLOW_LIVE_TOKEN=true.
    - Search is implemented first.
    - Quote/book/status remain blocked until the next adapter milestones.
    """

    code = "DUFFEL_SANDBOX"

    def __init__(self, sandbox: bool = True) -> None:
        self.sandbox = sandbox
        self.base_url = os.getenv("DUFFEL_BASE_URL", "https://api.duffel.com/air").rstrip("/")
        self.version = os.getenv("DUFFEL_VERSION", "v2")
        self.timeout = float(os.getenv("DUFFEL_TIMEOUT", "20"))

    def search(self, payload: Dict[str, Any]) -> Dict[str, Any]:
        self._validate_search_payload(payload)

        request_payload = {
            "data": {
                "slices": self._build_slices(payload),
                "passengers": self._build_passengers(payload),
                "cabin_class": self._map_cabin_class(payload.get("cabin_class")),
            }
        }

        response = self._post("/offer_requests", request_payload)
        data = response.get("data") or {}
        offers = data.get("offers") or []

        return {
            "search_id": data.get("id") or f"duffel_search_{uuid4().hex[:12]}",
            "supplier_code": self.code,
            "offers": [
                self._normalize_offer(offer, payload, data)
                for offer in offers
                if isinstance(offer, dict)
            ],
            "meta": {
                "mode": "duffel_sandbox",
                "supplier_code": self.code,
                "offer_request_id": data.get("id"),
                "raw_offer_count": len(offers),
            },
        }

    def quote(self, payload: Dict[str, Any]) -> Dict[str, Any]:
        supplier_context = dict(payload.get("supplier_context") or {})
        offer_snapshot = dict(payload.get("offer") or {})
        selected_fare = dict(payload.get("selected_fare") or {})

        offer_id = str(
            payload.get("offer_id")
            or supplier_context.get("raw_offer_id")
            or offer_snapshot.get("supplier_offer_id")
            or offer_snapshot.get("offer_id")
            or offer_snapshot.get("id")
            or ""
        ).strip()

        selected_fare_id = str(
            payload.get("selected_fare_id")
            or selected_fare.get("id")
            or selected_fare.get("fare_id")
            or ""
        ).strip()

        if not offer_id or not selected_fare_id:
            raise SupplierValidationError(
                "offer_id and selected_fare_id are required for Duffel quote",
                {
                    "missing_fields": [
                        field
                        for field, value in {
                            "offer_id": offer_id,
                            "selected_fare_id": selected_fare_id,
                        }.items()
                        if not value
                    ],
                },
            )

        latest_offer = self._get(
            f"/offers/{offer_id}",
            {"return_available_services": "false"},
        ).get("data") or {}

        if not latest_offer:
            raise SupplierAdapterError(
                "Duffel offer could not be retrieved",
                error_code="DUFFEL_OFFER_NOT_FOUND",
                status_code=422,
                details={"supplier_code": self.code, "offer_id": offer_id},
            )

        amount = self._safe_float(
            latest_offer.get("total_amount")
            or selected_fare.get("total_amount")
            or selected_fare.get("price")
            or offer_snapshot.get("total_amount")
            or offer_snapshot.get("price")
        )

        currency = str(
            latest_offer.get("total_currency")
            or selected_fare.get("currency")
            or offer_snapshot.get("currency")
            or "USD"
        ).upper()

        previous_amount = self._safe_float(
            selected_fare.get("total_amount")
            or selected_fare.get("price")
            or offer_snapshot.get("total_amount")
            or offer_snapshot.get("price")
        )

        price_changed = previous_amount > 0 and round(previous_amount, 2) != round(amount, 2)

        expires_at = latest_offer.get("expires_at") or offer_snapshot.get("expires_at")

        supplier_context.update(
            {
                "supplier_code": self.code,
                "raw_offer_id": offer_id,
                "pricing_token": offer_id,
                "quote_reference": offer_id,
                "expires_at": expires_at,
                "quote_validated_at": datetime.now(timezone.utc).isoformat(),
            }
        )

        normalized_latest_offer = self._normalize_offer(
            latest_offer,
            offer_snapshot,
            {"id": supplier_context.get("offer_request_id")},
        )

        return {
            "quote_id": f"duffel_quote_{offer_id}",
            "quote_uuid": f"duffel_quote_{offer_id}",
            "offer_id": offer_id,
            "selected_fare_id": selected_fare_id,
            "supplier_code": self.code,
            "confirmed_price": {
                "amount": amount,
                "currency": currency,
            },
            "confirmed_total_amount": amount,
            "currency": currency,
            "price_changed": price_changed,
            "expires_at": expires_at,
            "booking_requirements": self._quote_requirements(latest_offer),
            "checkout_fields": {
                "passport_required": True,
                "birth_date_required": True,
                "gender_required": True,
                "nationality_required": True,
            },
            "rules": {
                "refund": "Duffel offer conditions must be checked before ticketing.",
                "change": "Duffel offer conditions must be checked before ticketing.",
            },
            "supplier_context": supplier_context,
            "latest_offer": normalized_latest_offer,
            "status": "quoted",
            "raw": {
                "mode": "duffel_sandbox",
                "offer_id": offer_id,
                "latest_offer": latest_offer,
            },
        }

    def book(self, payload: Dict[str, Any]) -> Dict[str, Any]:
        raise SupplierAdapterError(
            "DUFFEL_SANDBOX book is not implemented yet",
            error_code="SUPPLIER_METHOD_NOT_IMPLEMENTED",
            status_code=501,
            details={"supplier_code": self.code, "next_step": "Implement Duffel order creation after payment"},
        )

    def status(self, reference: str) -> Dict[str, Any]:
        raise SupplierAdapterError(
            "DUFFEL_SANDBOX status is not implemented yet",
            error_code="SUPPLIER_METHOD_NOT_IMPLEMENTED",
            status_code=501,
            details={"supplier_code": self.code, "reference": reference},
        )

    def _validate_search_payload(self, payload: Dict[str, Any]) -> None:
        missing = [
            field
            for field in ["origin", "destination", "departure_date"]
            if not str(payload.get(field) or "").strip()
        ]

        if missing:
            raise SupplierValidationError(
                "origin, destination and departure_date are required for Duffel search",
                {"missing_fields": missing},
            )

        origin = str(payload.get("origin") or "").upper().strip()
        destination = str(payload.get("destination") or "").upper().strip()

        if origin == destination:
            raise SupplierValidationError(
                "origin and destination must be different",
                {"origin": origin, "destination": destination},
            )

    def _build_slices(self, payload: Dict[str, Any]) -> List[Dict[str, Any]]:
        slices = [
            {
                "origin": str(payload.get("origin") or "").upper().strip(),
                "destination": str(payload.get("destination") or "").upper().strip(),
                "departure_date": str(payload.get("departure_date") or "").split(" ")[0],
            }
        ]

        return_date = str(payload.get("return_date") or "").strip()
        if return_date:
            slices.append(
                {
                    "origin": str(payload.get("destination") or "").upper().strip(),
                    "destination": str(payload.get("origin") or "").upper().strip(),
                    "departure_date": return_date.split(" ")[0],
                }
            )

        return slices

    def _build_passengers(self, payload: Dict[str, Any]) -> List[Dict[str, str]]:
        passengers: List[Dict[str, str]] = []

        for _ in range(max(1, int(payload.get("adult_count") or 1))):
            passengers.append({"type": "adult"})

        for _ in range(max(0, int(payload.get("child_count") or 0))):
            passengers.append({"type": "child"})

        for _ in range(max(0, int(payload.get("infant_count") or 0))):
            passengers.append({"type": "infant_without_seat"})

        return passengers

    def _map_cabin_class(self, value: Any) -> str:
        raw = str(value or "economy").strip().lower().replace("-", "_").replace(" ", "_")

        aliases = {
            "eco": "economy",
            "economy": "economy",
            "premium": "premium_economy",
            "premium_economy": "premium_economy",
            "business": "business",
            "first": "first",
            "first_class": "first",
        }

        return aliases.get(raw, "economy")

    def _get(self, path: str, query: Dict[str, str] | None = None) -> Dict[str, Any]:
        token = self._token()
        url = self.base_url + path

        if query:
            url += "?" + urlencode(query)

        req = urllib_request.Request(
            url,
            method="GET",
            headers={
                "Authorization": f"Bearer {token}",
                "Duffel-Version": self.version,
                "Accept": "application/json",
            },
        )

        try:
            with urllib_request.urlopen(req, timeout=self.timeout) as response:
                raw_body = response.read().decode("utf-8")
                return json.loads(raw_body or "{}")
        except HTTPError as exc:
            raw_body = exc.read().decode("utf-8", errors="replace")
            parsed = self._parse_error_body(raw_body)
            raise SupplierAdapterError(
                "Duffel API request failed",
                error_code=self._extract_duffel_error_code(parsed),
                status_code=502 if exc.code >= 500 else 422,
                details={
                    "supplier_code": self.code,
                    "http_status": exc.code,
                    "response": parsed,
                },
            ) from exc
        except URLError as exc:
            raise SupplierAdapterError(
                "Duffel API connection failed",
                error_code="SUPPLIER_CONNECTION_FAILED",
                status_code=502,
                details={
                    "supplier_code": self.code,
                    "reason": str(exc.reason),
                },
            ) from exc

    def _post(self, path: str, payload: Dict[str, Any]) -> Dict[str, Any]:
        token = self._token()
        body = json.dumps(payload).encode("utf-8")

        req = urllib_request.Request(
            self.base_url + path,
            data=body,
            method="POST",
            headers={
                "Authorization": f"Bearer {token}",
                "Duffel-Version": self.version,
                "Content-Type": "application/json",
                "Accept": "application/json",
            },
        )

        try:
            with urllib_request.urlopen(req, timeout=self.timeout) as response:
                raw_body = response.read().decode("utf-8")
                return json.loads(raw_body or "{}")
        except HTTPError as exc:
            raw_body = exc.read().decode("utf-8", errors="replace")
            parsed = self._parse_error_body(raw_body)
            raise SupplierAdapterError(
                "Duffel API request failed",
                error_code=self._extract_duffel_error_code(parsed),
                status_code=502 if exc.code >= 500 else 422,
                details={
                    "supplier_code": self.code,
                    "http_status": exc.code,
                    "response": parsed,
                },
            ) from exc
        except URLError as exc:
            raise SupplierAdapterError(
                "Duffel API connection failed",
                error_code="SUPPLIER_CONNECTION_FAILED",
                status_code=502,
                details={
                    "supplier_code": self.code,
                    "reason": str(exc.reason),
                },
            ) from exc

    def _token(self) -> str:
        token = os.getenv("DUFFEL_API_TOKEN", "").strip()

        if not token:
            raise SupplierConfigurationError(
                "DUFFEL_SANDBOX adapter is not configured",
                {
                    "supplier_code": self.code,
                    "required_env": ["DUFFEL_API_TOKEN"],
                },
            )

        allow_live_token = os.getenv("DUFFEL_ALLOW_LIVE_TOKEN", "false").strip().lower() in {"1", "true", "yes", "on"}

        if self.sandbox and not token.startswith("duffel_test_") and not allow_live_token:
            raise SupplierConfigurationError(
                "DUFFEL_SANDBOX requires a Duffel test token",
                {
                    "supplier_code": self.code,
                    "required_token_prefix": "duffel_test_",
                    "override_env": "DUFFEL_ALLOW_LIVE_TOKEN=true",
                },
            )

        return token

    def _quote_requirements(self, offer: Dict[str, Any]) -> Dict[str, Any]:
        return {
            "contact": {
                "email": True,
                "phone": True,
            },
            "traveller": {
                "first_name": True,
                "last_name": True,
                "birth_date": True,
                "gender": True,
                "nationality": True,
                "passport_number": True,
                "passport_expiry": True,
            },
        }

    def _safe_float(self, value: Any) -> float:
        try:
            return round(float(value or 0), 2)
        except (TypeError, ValueError):
            return 0.0

    def _parse_error_body(self, raw_body: str) -> Dict[str, Any]:
        try:
            return json.loads(raw_body or "{}")
        except json.JSONDecodeError:
            return {"raw": raw_body}

    def _extract_duffel_error_code(self, parsed: Dict[str, Any]) -> str:
        errors = parsed.get("errors") or []
        if errors and isinstance(errors, list) and isinstance(errors[0], dict):
            code = errors[0].get("code")
            if code:
                return "DUFFEL_" + str(code).upper()

        return "DUFFEL_API_ERROR"

    def _normalize_offer(self, offer: Dict[str, Any], payload: Dict[str, Any], offer_request: Dict[str, Any]) -> Dict[str, Any]:
        slices = offer.get("slices") or []
        first_slice = slices[0] if slices else {}
        segments = first_slice.get("segments") or []
        first_segment = segments[0] if segments else {}
        last_segment = segments[-1] if segments else first_segment

        origin = self._airport_code(first_segment.get("origin")) or str(payload.get("origin") or "").upper()
        destination = self._airport_code(last_segment.get("destination")) or str(payload.get("destination") or "").upper()
        departure_at = first_segment.get("departing_at")
        arrival_at = last_segment.get("arriving_at")
        duration_minutes = self._duration_minutes(first_slice.get("duration"))
        stop_count = max(0, len(segments) - 1)

        airline = first_segment.get("marketing_carrier") or offer.get("owner") or {}
        airline_code = airline.get("iata_code") if isinstance(airline, dict) else None
        airline_name = airline.get("name") if isinstance(airline, dict) else None

        amount = float(offer.get("total_amount") or 0)
        currency = str(offer.get("total_currency") or payload.get("currency") or "USD").upper()
        offer_id = str(offer.get("id") or f"duffel_offer_{uuid4().hex[:12]}")

        return {
            "offer_id": offer_id,
            "id": offer_id,
            "supplier": self.code,
            "supplier_code": self.code,
            "supplier_offer_id": offer_id,
            "origin": origin,
            "destination": destination,
            "departure_at": departure_at,
            "arrival_at": arrival_at,
            "duration_minutes": duration_minutes,
            "stop_count": stop_count,
            "airline": {
                "code": airline_code,
                "name": airline_name or airline_code or "Duffel Airline",
            },
            "airline_code": airline_code,
            "airline_name": airline_name or airline_code or "Duffel Airline",
            "segments": [self._normalize_segment(segment) for segment in segments],
            "fare_options": [
                {
                    "id": f"{offer_id}_standard",
                    "fare_id": f"{offer_id}_standard",
                    "code": "standard",
                    "name": "Standard",
                    "title": "Standard",
                    "price": amount,
                    "total_amount": amount,
                    "amount": amount,
                    "currency": currency,
                    "display_price": f"{currency} {amount:,.2f}",
                    "checked_baggage": None,
                    "cabin_baggage": None,
                    "refundable": False,
                    "exchangeable": True,
                    "features": ["Duffel live availability", "Quote required before payment"],
                }
            ],
            "price": {
                "amount": amount,
                "currency": currency,
            },
            "total_amount": amount,
            "currency": currency,
            "expires_at": offer.get("expires_at"),
            "rules": {
                "refund": "Quote and fare conditions must be checked before booking.",
                "change": "Quote and fare conditions must be checked before booking.",
            },
            "capabilities": {
                "branded_fares_supported": False,
                "checked_baggage_supported": True,
                "seat_selection_supported": False,
                "hold_supported": False,
                "instant_ticketing_supported": False,
                "passport_required": True,
                "birth_date_required": True,
                "gender_required": True,
                "nationality_required": True,
            },
            "supplier_context": {
                "supplier_code": self.code,
                "raw_offer_id": offer_id,
                "pricing_token": offer_id,
                "offer_request_id": offer_request.get("id"),
                "expires_at": offer.get("expires_at"),
                "quote_required": True,
            },
            "raw": offer,
        }

    def _airport_code(self, value: Any) -> str | None:
        if isinstance(value, dict):
            return value.get("iata_code") or value.get("code")

        return None

    def _normalize_segment(self, segment: Dict[str, Any]) -> Dict[str, Any]:
        marketing = segment.get("marketing_carrier") or {}

        return {
            "origin": self._airport_code(segment.get("origin")),
            "destination": self._airport_code(segment.get("destination")),
            "departure_at": segment.get("departing_at"),
            "arrival_at": segment.get("arriving_at"),
            "airline_code": marketing.get("iata_code") if isinstance(marketing, dict) else None,
            "airline_name": marketing.get("name") if isinstance(marketing, dict) else None,
            "flight_number": segment.get("marketing_carrier_flight_number"),
            "duration_minutes": self._duration_minutes(segment.get("duration")),
        }

    def _duration_minutes(self, value: Any) -> int:
        raw = str(value or "")
        match = re.match(r"^P?T(?:(\d+)H)?(?:(\d+)M)?", raw)

        if not match:
            return 0

        hours = int(match.group(1) or 0)
        minutes = int(match.group(2) or 0)

        return hours * 60 + minutes
