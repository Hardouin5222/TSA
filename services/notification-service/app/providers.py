from dataclasses import dataclass
from uuid import uuid4

from app.core.settings import NotificationServiceSettings
from app.models import Notification


@dataclass(slots=True)
class NotificationDispatchResult:
    provider: str
    provider_reference: str
    status: str
    error_message: str | None = None


class NotificationProviderError(Exception):
    pass


class BaseNotificationProvider:
    provider_name: str

    def __init__(self, settings: NotificationServiceSettings) -> None:
        self.settings = settings

    def dispatch(self, notification: Notification) -> NotificationDispatchResult:
        raise NotImplementedError


class MockNotificationProvider(BaseNotificationProvider):
    provider_name = "mock-notifier"

    def dispatch(self, notification: Notification) -> NotificationDispatchResult:
        booking_key = notification.booking_reference.lower()
        provider_reference = (
            notification.provider_reference
            or f"{self.settings.notification_mock_reference_prefix}_{booking_key}_{uuid4().hex[:8]}"
        )
        return NotificationDispatchResult(
            provider=self.provider_name,
            provider_reference=provider_reference,
            status="sent",
        )


class UnsupportedNotificationProvider(BaseNotificationProvider):
    def __init__(self, provider_name: str, settings: NotificationServiceSettings) -> None:
        super().__init__(settings)
        self.provider_name = provider_name

    def dispatch(self, notification: Notification) -> NotificationDispatchResult:
        raise NotificationProviderError(
            f"Notification provider '{self.provider_name}' is configured but not implemented yet."
        )


def get_notification_provider(settings: NotificationServiceSettings) -> BaseNotificationProvider:
    normalized = settings.notification_provider.strip().lower()
    if normalized == "mock":
        return MockNotificationProvider(settings)

    return UnsupportedNotificationProvider(normalized, settings)
