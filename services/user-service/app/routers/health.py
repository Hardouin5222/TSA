from fastapi import APIRouter

from travel_shared.responses import success_response

router = APIRouter(tags=["health"])


@router.get("/health/live")
async def live_healthcheck() -> dict:
    return success_response({"status": "live"})


@router.get("/health/ready")
async def ready_healthcheck() -> dict:
    return success_response({"status": "ready"})
