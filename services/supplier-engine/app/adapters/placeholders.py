from __future__ import annotations

from typing import Any, Dict

from .base import FlightSupplierAdapter
from .errors import SupplierConfigurationError


class NotConfiguredFlightAdapter(FlightSupplierAdapter):
    """Safe placeholder for real supplier adapters.

    This lets us register future live/sandbox supplier modes now without risking
    silent mock behavior or raw 500 errors when credentials are missing.
    """

    code = "NOT_CONFIGURED"

    def __init__(self, code: str, required_env: list[str]) -> None:
        self.code = code
        self.required_env = required_env

    def _raise_not_configured(self) -> None:
        raise SupplierConfigurationError(
            f"{self.code} adapter is not configured",
            {
                "supplier_code": self.code,
                "required_env": self.required_env,
            },
        )

    def search(self, payload: Dict[str, Any]) -> Dict[str, Any]:
        self._raise_not_configured()

    def quote(self, payload: Dict[str, Any]) -> Dict[str, Any]:
        self._raise_not_configured()

    def book(self, payload: Dict[str, Any]) -> Dict[str, Any]:
        self._raise_not_configured()

    def status(self, reference: str) -> Dict[str, Any]:
        self._raise_not_configured()
