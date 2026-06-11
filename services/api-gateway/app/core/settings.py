from pydantic import Field

from travel_shared.config import BaseServiceSettings, get_settings


class GatewaySettings(BaseServiceSettings):
    user_service_base_url: str = Field(alias="USER_SERVICE_BASE_URL")
    flight_service_base_url: str = Field(alias="FLIGHT_SERVICE_BASE_URL")
    cart_service_base_url: str = Field(alias="CART_SERVICE_BASE_URL")
    payment_service_base_url: str = Field(alias="PAYMENT_SERVICE_BASE_URL")
    booking_service_base_url: str = Field(alias="BOOKING_SERVICE_BASE_URL")


def get_gateway_settings() -> GatewaySettings:
    return get_settings(GatewaySettings)  # type: ignore[return-value]
