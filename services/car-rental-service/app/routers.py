from fastapi import APIRouter

from app.schemas import CarRentalSearchRequest
from app.service import search_cars
from travel_shared.responses import success_response

router = APIRouter(prefix="/cars", tags=["cars"])


@router.post("/search")
async def car_search(payload: CarRentalSearchRequest) -> dict:
    result = search_cars(payload)
    return success_response(result.model_dump(), message="Car rental offers fetched successfully")
