from travel_shared.config import BaseServiceSettings, get_settings


class BookingServiceSettings(BaseServiceSettings):
    pass


def get_booking_service_settings() -> BookingServiceSettings:
    return get_settings(BookingServiceSettings)  # type: ignore[return-value]
