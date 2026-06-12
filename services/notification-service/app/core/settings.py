from pydantic import Field

from travel_shared.config import BaseServiceSettings, get_settings


class NotificationServiceSettings(BaseServiceSettings):
    notification_provider: str = Field(default="mock", alias="NOTIFICATION_PROVIDER")
    notification_sender_name: str = Field(default="Travel Super App", alias="NOTIFICATION_SENDER_NAME")
    notification_sender_email: str = Field(
        default="noreply@travel-super-app.local",
        alias="NOTIFICATION_SENDER_EMAIL",
    )
    notification_mock_reference_prefix: str = Field(
        default="mocknotif",
        alias="NOTIFICATION_MOCK_REFERENCE_PREFIX",
    )


def get_notification_service_settings() -> NotificationServiceSettings:
    return get_settings(NotificationServiceSettings)  # type: ignore[return-value]
