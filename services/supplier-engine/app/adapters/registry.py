from __future__ import annotations

import os
from typing import Dict

from .base import FlightSupplierAdapter
from .mock import MockFlightAdapter
from .placeholders import NotConfiguredFlightAdapter


_ADAPTERS: Dict[str, FlightSupplierAdapter] = {
    "mock": MockFlightAdapter(),
    "mock_supplier_engine": MockFlightAdapter(),
    "MOCK_SUPPLIER_ENGINE": MockFlightAdapter(),
    "duffel_sandbox": NotConfiguredFlightAdapter("DUFFEL_SANDBOX", ["DUFFEL_API_TOKEN"]),
    "mystifly_sandbox": NotConfiguredFlightAdapter("MYSTIFLY_SANDBOX", ["MYSTIFLY_USERNAME", "MYSTIFLY_PASSWORD", "MYSTIFLY_ACCOUNT_NUMBER"]),
    "biletbank_sandbox": NotConfiguredFlightAdapter("BILETBANK_SANDBOX", ["BILETBANK_USERNAME", "BILETBANK_PASSWORD", "BILETBANK_AGENCY_CODE"]),
}


def get_flight_adapter(mode: str | None = None) -> FlightSupplierAdapter:
    """Return the configured flight supplier adapter.

    TSA_SUPPLIER_ENGINE_MODE currently maps to the mock adapter.
    Future modes can be added here without changing API endpoints:
    - duffel_sandbox
    - mystifly_sandbox
    - biletbank_sandbox
    - live
    """

    selected_mode = (mode or os.getenv("TSA_SUPPLIER_ENGINE_MODE") or "mock").strip()

    adapter = _ADAPTERS.get(selected_mode) or _ADAPTERS.get(selected_mode.lower())
    if adapter:
        return adapter

    # Safe fallback while the supplier engine is still in MVP adapter mode.
    return _ADAPTERS["mock"]
