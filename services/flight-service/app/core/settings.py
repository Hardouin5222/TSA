from travel_shared.config import BaseServiceSettings, get_settings


class FlightServiceSettings(BaseServiceSettings):
    flight_data_mode: str = "mock_supplier"
    flight_mock_catalog_path: str | None = None


def get_flight_service_settings() -> FlightServiceSettings:
    return get_settings(FlightServiceSettings)  # type: ignore[return-value]
