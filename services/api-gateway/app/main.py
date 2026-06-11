from fastapi import FastAPI

from app.core.settings import get_gateway_settings
from app.routers.cart import router as cart_router
from app.routers import health, root
from app.routers.flights import router as flights_router
from app.routers.payments import router as payments_router
from travel_shared.logging import configure_logging
from travel_shared.middleware import CorrelationIdMiddleware

settings = get_gateway_settings()
configure_logging(settings.service_name)

app = FastAPI(
    title="Travel Super App API Gateway",
    version="0.1.0",
    debug=settings.debug,
)
app.add_middleware(CorrelationIdMiddleware)

app.include_router(root.router)
app.include_router(health.router, prefix=settings.api_prefix)
app.include_router(cart_router, prefix=settings.api_prefix)
app.include_router(flights_router, prefix=settings.api_prefix)
app.include_router(payments_router, prefix=settings.api_prefix)
