from fastapi import APIRouter

from app.schemas import FlightSearchRequest
from app.service import search_flights
from travel_shared.responses import success_response

router = APIRouter(prefix="/flights", tags=["flights"])


@router.post("/search")
async def flight_search(payload: FlightSearchRequest) -> dict:
    result = search_flights(payload)
    return success_response(result.model_dump(), message="Flight offers fetched successfully")
