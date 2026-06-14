from fastapi import APIRouter

from travel_shared.responses import success_response

router = APIRouter(tags=["root"])


@router.get("/")
async def root() -> dict:
    return success_response(
        {
            "service": "api-gateway",
            "description": "Travel Super App API Gateway",
        }
    )
