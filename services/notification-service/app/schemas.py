from pydantic import BaseModel, Field


class CreateBookingConfirmationNotificationRequest(BaseModel):
    booking_reference: str = Field(min_length=1)
    booking_id: str = Field(min_length=1)
    user_id: str | None = None
    guest_session_id: str | None = None
    channel: str = Field(default="email", min_length=1)
    recipient_email: str | None = None
    recipient_phone: str | None = None
    locale: str = Field(default="tr-TR")
    currency: str = Field(min_length=1)
    total_amount: float = Field(gt=0)
    booking_url: str = Field(min_length=1)
    trip_summary: str = Field(min_length=1)


class NotificationResponse(BaseModel):
    notification_id: str
    booking_reference: str
    template_code: str
    channel: str
    status: str
    recipient_email: str | None
    recipient_phone: str | None
    provider: str
    subject: str | None = None
    content_preview: str | None = None
    provider_reference: str | None = None
    sent_at: str | None = None


class NotificationListItemResponse(BaseModel):
    notification_id: str
    booking_reference: str
    template_code: str
    channel: str
    status: str
    recipient_email: str | None
    recipient_phone: str | None
    provider: str
    created_at: str


class NotificationListResponse(BaseModel):
    notifications: list[NotificationListItemResponse]


class DispatchNotificationResponse(BaseModel):
    notification_id: str
    booking_reference: str
    status: str
    provider_reference: str | None
    sent_at: str | None


class ClaimGuestNotificationRequest(BaseModel):
    guest_session_id: str = Field(min_length=1, max_length=100)
    user_id: str = Field(min_length=1, max_length=100)
    recipient_email: str | None = None
    recipient_phone: str | None = None


class ClaimGuestNotificationResponse(BaseModel):
    claimed_count: int
