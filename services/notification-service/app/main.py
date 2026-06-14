from fastapi import FastAPI

from app.core.settings import get_notification_service_settings
from app.core.database import Base, engine
from app import models  # noqa: F401
from app.routers import router as notification_router
from travel_shared.logging import configure_logging
from travel_shared.middleware import CorrelationIdMiddleware
from travel_shared.responses import success_response

settings = get_notification_service_settings()
configure_logging(settings.service_name)

app = FastAPI(
    title="Travel Super App Notification Service",
    version="0.1.0",
    debug=settings.debug,
)
app.add_middleware(CorrelationIdMiddleware)


@app.on_event("startup")
async def startup() -> None:
    Base.metadata.create_all(bind=engine)


@app.get("/api/health/live")
async def live_healthcheck() -> dict:
    return success_response({"status": "live"})


@app.get("/api/health/ready")
async def ready_healthcheck() -> dict:
    return success_response({"status": "ready"})


app.include_router(notification_router, prefix=settings.api_prefix)
