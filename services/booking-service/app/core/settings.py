from pydantic import Field

from travel_shared.config import BaseServiceSettings, get_settings


class BookingServiceSettings(BaseServiceSettings):
    notification_service_base_url: str = Field(
        default="http://notification-service:8006",
        alias="NOTIFICATION_SERVICE_BASE_URL",
    )


def get_booking_service_settings() -> BookingServiceSettings:
    return get_settings(BookingServiceSettings)  # type: ignore[return-value]
