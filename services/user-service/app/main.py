from fastapi import FastAPI

from app.core.settings import get_user_service_settings
from app.routers import auth, health, users
from travel_shared.logging import configure_logging
from travel_shared.middleware import CorrelationIdMiddleware

settings = get_user_service_settings()
configure_logging(settings.service_name)

app = FastAPI(
    title="Travel Super App User Service",
    version="0.1.0",
    debug=settings.debug,
)
app.add_middleware(CorrelationIdMiddleware)

app.include_router(auth.router, prefix=settings.api_prefix)
app.include_router(health.router, prefix=settings.api_prefix)
app.include_router(users.router, prefix=settings.api_prefix)
