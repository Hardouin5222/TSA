from __future__ import annotations

from typing import Any, Dict, List, Optional

from fastapi import FastAPI, HTTPException
from fastapi.middleware.cors import CORSMiddleware
from pydantic import BaseModel, Field

from app.adapters import SupplierAdapterError, get_flight_adapter

app = FastAPI(title="TSA Supplier Engine", version="0.1.8")

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


def _raise_adapter_error(exc: SupplierAdapterError) -> None:
    raise HTTPException(
        status_code=exc.status_code,
        detail={
            "error_code": exc.error_code,
            "message": exc.message,
            "details": exc.details,
        },
    ) from exc

@app.get("/health")
def health() -> Dict[str, str]:
    return {"status": "ok", "service": "tsa-supplier-engine"}


@app.get("/api/health")
def api_health() -> Dict[str, str]:
    return health()


@app.post("/api/flights/search")
def search_flights(payload: FlightSearchRequest) -> Dict[str, Any]:
    adapter = get_flight_adapter()

    try:
        return adapter.search(_payload_dict(payload))
    except SupplierAdapterError as exc:
        _raise_adapter_error(exc)
    except ValueError as exc:
        raise HTTPException(status_code=422, detail=str(exc)) from exc

@app.post("/api/flights/quote")
def quote_flight(payload: FlightQuoteRequest) -> Dict[str, Any]:
    adapter = get_flight_adapter()

    try:
        return adapter.quote(_payload_dict(payload))
    except SupplierAdapterError as exc:
        _raise_adapter_error(exc)
    except ValueError as exc:
        raise HTTPException(status_code=422, detail=str(exc)) from exc

@app.post("/api/flights/book")
def book_flight(payload: FlightBookRequest) -> Dict[str, Any]:
    adapter = get_flight_adapter()

    try:
        return adapter.book(_payload_dict(payload))
    except SupplierAdapterError as exc:
        _raise_adapter_error(exc)
    except ValueError as exc:
        raise HTTPException(status_code=422, detail=str(exc)) from exc

@app.get("/api/flights/bookings/{reference}/status")
def flight_booking_status(reference: str) -> Dict[str, Any]:
    adapter = get_flight_adapter()

    try:
        return adapter.status(reference)
    except SupplierAdapterError as exc:
        _raise_adapter_error(exc)
    except ValueError as exc:
        raise HTTPException(status_code=422, detail=str(exc)) from exc
