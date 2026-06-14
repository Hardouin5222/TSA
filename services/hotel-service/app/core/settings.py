from travel_shared.config import BaseServiceSettings, get_settings


class HotelServiceSettings(BaseServiceSettings):
    hotel_data_mode: str = "mock_supplier"
    hotel_mock_catalog_path: str | None = None


def get_hotel_service_settings() -> HotelServiceSettings:
    return get_settings(HotelServiceSettings)  # type: ignore[return-value]
