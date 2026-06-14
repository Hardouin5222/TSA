from pydantic import Field

from travel_shared.config import BaseServiceSettings, get_settings


class PaymentServiceSettings(BaseServiceSettings):
    payment_provider: str = Field(default="iyzico", alias="PAYMENT_PROVIDER")


def get_payment_service_settings() -> PaymentServiceSettings:
    return get_settings(PaymentServiceSettings)  # type: ignore[return-value]
