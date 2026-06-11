from travel_shared.config import BaseServiceSettings, get_settings


class FlightServiceSettings(BaseServiceSettings):
    pass


def get_flight_service_settings() -> FlightServiceSettings:
    return get_settings(FlightServiceSettings)  # type: ignore[return-value]
