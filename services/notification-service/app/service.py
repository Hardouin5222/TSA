from datetime import UTC, datetime

from fastapi import HTTPException, status
from sqlalchemy import select
from sqlalchemy.orm import Session

from app.models import Notification
from app.schemas import (
    ClaimGuestNotificationRequest,
    ClaimGuestNotificationResponse,
    CreateBookingConfirmationNotificationRequest,
    DispatchNotificationResponse,
    NotificationListItemResponse,
    NotificationListResponse,
    NotificationResponse,
)


def create_booking_confirmation_notification(
    payload: CreateBookingConfirmationNotificationRequest,
    db: Session,
) -> NotificationResponse:
    existing = db.scalar(
        select(Notification).where(
            Notification.booking_reference == payload.booking_reference,
            Notification.template_code == "booking_confirmation",
            Notification.channel == payload.channel,
        )
    )
    if existing:
        return _serialize_notification(existing)

    recipient_resolved = bool(payload.recipient_email or payload.recipient_phone)
    status = "queued" if recipient_resolved else "pending_recipient"

    notification = Notification(
        booking_reference=payload.booking_reference,
        booking_id=payload.booking_id,
        user_id=payload.user_id,
        guest_session_id=payload.guest_session_id,
        template_code="booking_confirmation",
        channel=payload.channel,
        status=status,
        provider="mock-notifier",
        recipient_email=payload.recipient_email,
        recipient_phone=payload.recipient_phone,
        subject=f"Rezervasyon onayi - {payload.booking_reference}",
        content_preview=f"{payload.trip_summary} rezervasyonun hazir. Toplam {payload.total_amount} {payload.currency}.",
        payload={
            "booking_url": payload.booking_url,
            "trip_summary": payload.trip_summary,
            "locale": payload.locale,
        },
        total_amount=payload.total_amount,
        currency=payload.currency,
        provider_reference=f"notif_{payload.booking_reference.lower()}",
    )
    db.add(notification)
    db.commit()
    db.refresh(notification)
    return _serialize_notification(notification)


def list_notifications(
    booking_reference: str | None,
    user_id: str | None,
    recipient_email: str | None,
    db: Session,
) -> NotificationListResponse:
    query = select(Notification).order_by(Notification.created_at.desc())
    if booking_reference:
        query = query.where(Notification.booking_reference == booking_reference)
    if user_id:
        query = query.where(Notification.user_id == user_id)
    if recipient_email:
        query = query.where(Notification.recipient_email == recipient_email)

    notifications = db.scalars(query).all()
    return NotificationListResponse(
        notifications=[
            NotificationListItemResponse(
                notification_id=str(item.id),
                booking_reference=item.booking_reference,
                template_code=item.template_code,
                channel=item.channel,
                status=item.status,
                recipient_email=item.recipient_email,
                recipient_phone=item.recipient_phone,
                provider=item.provider,
                created_at=item.created_at.isoformat(),
            )
            for item in notifications
        ]
    )


def claim_guest_notifications(
    payload: ClaimGuestNotificationRequest,
    db: Session,
) -> ClaimGuestNotificationResponse:
    notifications = db.scalars(
        select(Notification).where(
            Notification.guest_session_id == payload.guest_session_id,
            Notification.user_id.is_(None),
        )
    ).all()

    for notification in notifications:
        notification.user_id = payload.user_id
        notification.guest_session_id = None
        if payload.recipient_email:
            notification.recipient_email = payload.recipient_email
        if payload.recipient_phone:
            notification.recipient_phone = payload.recipient_phone
        if notification.status == "pending_recipient" and (
            notification.recipient_email or notification.recipient_phone
        ):
            notification.status = "queued"

    db.commit()
    return ClaimGuestNotificationResponse(claimed_count=len(notifications))


def dispatch_notification(notification_id: str, db: Session) -> DispatchNotificationResponse:
    notification = db.get(Notification, notification_id)
    if not notification:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Notification not found")

    if not notification.recipient_email and not notification.recipient_phone:
        raise HTTPException(status_code=status.HTTP_400_BAD_REQUEST, detail="Notification recipient is missing")

    notification.status = "sent"
    notification.sent_at = datetime.now(UTC)
    db.commit()
    db.refresh(notification)

    return DispatchNotificationResponse(
        notification_id=str(notification.id),
        booking_reference=notification.booking_reference,
        status=notification.status,
        provider_reference=notification.provider_reference,
        sent_at=notification.sent_at.isoformat() if notification.sent_at else None,
    )


def _serialize_notification(notification: Notification) -> NotificationResponse:
    return NotificationResponse(
        notification_id=str(notification.id),
        booking_reference=notification.booking_reference,
        template_code=notification.template_code,
        channel=notification.channel,
        status=notification.status,
        recipient_email=notification.recipient_email,
        recipient_phone=notification.recipient_phone,
        provider=notification.provider,
        subject=notification.subject,
        content_preview=notification.content_preview,
        provider_reference=notification.provider_reference,
        sent_at=notification.sent_at.isoformat() if notification.sent_at else None,
    )
