from fastapi import FastAPI

from app.core.settings import get_car_rental_service_settings
from app.routers import router as car_router
from travel_shared.logging import configure_logging
from travel_shared.middleware import CorrelationIdMiddleware
from travel_shared.responses import success_response

settings = get_car_rental_service_settings()
configure_logging(settings.service_name)

app = FastAPI(
    title="Travel Super App Car Rental Service",
    version="0.1.0",
    debug=settings.debug,
)
app.add_middleware(CorrelationIdMiddleware)


@app.get("/api/health/live")
async def live_healthcheck() -> dict:
    return success_response({"status": "live"})


@app.get("/api/health/ready")
async def ready_healthcheck() -> dict:
    return success_response({"status": "ready"})


app.include_router(car_router, prefix=settings.api_prefix)
