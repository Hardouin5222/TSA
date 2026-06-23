from __future__ import annotations

from abc import ABC, abstractmethod
from typing import Any, Dict


class FlightSupplierAdapter(ABC):
    """Normalized supplier adapter contract for TSA Supplier Engine.

    Every real supplier adapter must return Booking Core compatible normalized
    payloads from these four methods:

    - search: normalized flight offers
    - quote: confirmed price, requirements, rules and supplier_context
    - book: supplier booking reference, PNR, ticket numbers and fulfillment status
    - status: supplier booking/ticketing status lookup
    """

    code: str = "BASE"

    @abstractmethod
    def search(self, payload: Dict[str, Any]) -> Dict[str, Any]:
        raise NotImplementedError

    @abstractmethod
    def quote(self, payload: Dict[str, Any]) -> Dict[str, Any]:
        raise NotImplementedError

    @abstractmethod
    def book(self, payload: Dict[str, Any]) -> Dict[str, Any]:
        raise NotImplementedError

    @abstractmethod
    def status(self, reference: str) -> Dict[str, Any]:
        raise NotImplementedError
