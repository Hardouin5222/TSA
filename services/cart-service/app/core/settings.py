from pydantic import Field

from travel_shared.config import BaseServiceSettings, get_settings


class CartServiceSettings(BaseServiceSettings):
    jwt_algorithm: str = Field(default="HS256")


def get_cart_service_settings() -> CartServiceSettings:
    return get_settings(CartServiceSettings)  # type: ignore[return-value]
