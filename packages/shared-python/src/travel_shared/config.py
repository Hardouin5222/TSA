from functools import lru_cache

from pydantic import Field
from pydantic_settings import BaseSettings, SettingsConfigDict


class BaseServiceSettings(BaseSettings):
    app_env: str = Field(default="local", alias="APP_ENV")
    service_name: str = Field(alias="SERVICE_NAME")
    service_port: int = Field(alias="SERVICE_PORT")
    api_prefix: str = Field(default="/api")
    debug: bool = Field(default=False)

    database_url: str = Field(alias="DATABASE_URL")
    redis_url: str = Field(alias="REDIS_URL")
    rabbitmq_url: str = Field(alias="RABBITMQ_URL")

    jwt_secret_key: str = Field(default="change-me-in-production", alias="JWT_SECRET_KEY")
    jwt_refresh_secret_key: str = Field(default="change-me-too", alias="JWT_REFRESH_SECRET_KEY")

    model_config = SettingsConfigDict(
        env_file=".env",
        env_file_encoding="utf-8",
        case_sensitive=False,
        extra="ignore",
    )


@lru_cache
def get_settings(settings_cls: type[BaseServiceSettings]) -> BaseServiceSettings:
    return settings_cls()
