from fastapi import APIRouter
from sqlalchemy import text

from app.core.database import SessionLocal
from travel_shared.responses import success_response

router = APIRouter(tags=["health"])


@router.get("/health/live")
async def live_healthcheck() -> dict:
    return success_response({"status": "live"})


@router.get("/health/ready")
async def ready_healthcheck() -> dict:
    with SessionLocal() as session:
        session.execute(text("SELECT 1"))
    return success_response({"status": "ready"})
