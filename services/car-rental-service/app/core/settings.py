from travel_shared.config import BaseServiceSettings, get_settings


class CarRentalServiceSettings(BaseServiceSettings):
    car_data_mode: str = "mock_supplier"
    car_mock_catalog_path: str | None = None


def get_car_rental_service_settings() -> CarRentalServiceSettings:
    return get_settings(CarRentalServiceSettings)  # type: ignore[return-value]
