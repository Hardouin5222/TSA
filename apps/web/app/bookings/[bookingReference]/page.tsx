import { BookingDetailContent } from "@/components/booking/booking-detail-content";
import { serverApiRequest } from "@/lib/api";
import type { BookingDetailEnvelope } from "@/types/booking";
import type { NotificationListEnvelope } from "@/types/notification";

export default async function BookingDetailPage({
  params,
}: {
  params: Promise<{ bookingReference: string }>;
}) {
  const { bookingReference } = await params;
  const [bookingPayload, notificationPayload] = await Promise.all([
    serverApiRequest<BookingDetailEnvelope>(`/api/bookings/reference/${bookingReference}`),
    serverApiRequest<NotificationListEnvelope>(`/api/notifications/?booking_reference=${bookingReference}`),
  ]);

  return (
    <BookingDetailContent
      booking={bookingPayload.data}
      notification={notificationPayload.data.notifications[0] || null}
    />
  );
}
