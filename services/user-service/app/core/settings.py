from pydantic import Field

from travel_shared.config import BaseServiceSettings, get_settings


class UserServiceSettings(BaseServiceSettings):
    jwt_algorithm: str = Field(default="HS256")
    access_token_expire_minutes: int = Field(default=30)
    refresh_token_expire_days: int = Field(default=30)


def get_user_service_settings() -> UserServiceSettings:
    return get_settings(UserServiceSettings)  # type: ignore[return-value]
