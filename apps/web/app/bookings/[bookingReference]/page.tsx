import { BookingDetailContent } from "@/components/booking/booking-detail-content";
import { serverApiRequest } from "@/lib/api";
import type { BookingDetailEnvelope } from "@/types/booking";

export default async function BookingDetailPage({
  params,
}: {
  params: Promise<{ bookingReference: string }>;
}) {
  const { bookingReference } = await params;
  const payload = await serverApiRequest<BookingDetailEnvelope>(`/api/bookings/reference/${bookingReference}`);

  return <BookingDetailContent booking={payload.data} />;
}
