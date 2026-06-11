from travel_shared.config import BaseServiceSettings, get_settings


class NotificationServiceSettings(BaseServiceSettings):
    pass


def get_notification_service_settings() -> NotificationServiceSettings:
    return get_settings(NotificationServiceSettings)  # type: ignore[return-value]
