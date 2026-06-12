from fastapi import APIRouter

from app.schemas import HotelSearchRequest
from app.service import search_hotels
from travel_shared.responses import success_response

router = APIRouter(prefix="/hotels", tags=["hotels"])


@router.post("/search")
async def hotel_search(payload: HotelSearchRequest) -> dict:
    result = search_hotels(payload)
    return success_response(result.model_dump(), message="Hotel offers fetched successfully")
