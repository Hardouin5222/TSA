from travel_shared.config import BaseServiceSettings, get_settings


class UserServiceSettings(BaseServiceSettings):
    pass


def get_user_service_settings() -> UserServiceSettings:
    return get_settings(UserServiceSettings)  # type: ignore[return-value]
