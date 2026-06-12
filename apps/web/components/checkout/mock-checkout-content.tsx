"use client";

import Link from "next/link";
import { useState } from "react";

import { apiRequest } from "@/lib/api";
import { getSession } from "@/lib/auth";
import type { BookingEnvelope } from "@/types/booking";
import type { PaymentIntentEnvelope } from "@/types/payment";

type PaymentIntentDetail = PaymentIntentEnvelope["data"] & {
  cart_id: string;
  user_id: string | null;
  guest_session_id: string | null;
  contact: {
    email: string;
    phone: string;
  };
  travelers: Array<{
    traveler_type: string;
    first_name: string;
    last_name: string;
    birth_date: string;
  }>;
  items: Array<{
    id: string;
    item_type: string;
    reference_id: string;
    title: string;
    quantity: number;
    unit_price: number;
    currency: string;
    item_payload: Record<string, unknown>;
  }>;
};

function formatPrice(value: number, currency: string) {
  return new Intl.NumberFormat("tr-TR", {
    style: "currency",
    currency,
    maximumFractionDigits: 0,
  }).format(value);
}

export function MockCheckoutContent({
  providerReference,
  intent,
}: {
  providerReference: string;
  intent: PaymentIntentDetail;
}) {
  const [confirmedIntent, setConfirmedIntent] = useState<PaymentIntentDetail>(intent);
  const [booking, setBooking] = useState<BookingEnvelope["data"] | null>(null);
  const [feedback, setFeedback] = useState<string | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [isProcessing, setIsProcessing] = useState(false);

  async function handleConfirmAndCreateBooking() {
    setIsProcessing(true);
    setFeedback(null);
    setError(null);

    try {
      const confirmed = await apiRequest<{ success: boolean; message: string; data: PaymentIntentDetail }>(
        `/api/payments/intents/${providerReference}/confirm`,
        { method: "POST" },
      );

      setConfirmedIntent(confirmed.data);

      const bookingPayload = await apiRequest<BookingEnvelope>("/api/bookings/from-payment", {
        method: "POST",
        body: {
          payment_intent_id: confirmed.data.payment_intent_id,
          provider_reference: confirmed.data.provider_reference,
          cart_id: confirmed.data.cart_id,
          user_id: confirmed.data.user_id,
          guest_session_id: confirmed.data.guest_session_id,
          customer_email: confirmed.data.contact.email || getSession()?.user.email || null,
          customer_phone: confirmed.data.contact.phone || getSession()?.user.phone_number || null,
          total_amount: confirmed.data.amount,
          currency: confirmed.data.currency,
          items: confirmed.data.items,
          contact: confirmed.data.contact,
          travelers: confirmed.data.travelers,
        },
      });

      setBooking(bookingPayload.data);
      setFeedback("Odeme onayi ve rezervasyon kaydi basariyla olusturuldu.");
    } catch (requestError) {
      setError(requestError instanceof Error ? requestError.message : "Checkout flow failed");
    } finally {
      setIsProcessing(false);
    }
  }

  return (
    <main className="cart-page-shell">
      <div className="results-breadcrumb">
        <Link href="/cart">Sepete don</Link>
        <span>/</span>
        <span>Mock checkout</span>
      </div>

      <section className="results-shell">
        <div className="results-header-card">
          <span className="eyebrow">Mock Checkout</span>
          <h1>Odeme niyeti hazir</h1>
          <p>
            Bu ekran iyzico oncesi ilk kontrollu odeme akisini simule eder. Buradan odemeyi tamamlanmis
            varsayip booking kaydi aciyoruz.
          </p>
        </div>

        <div className="results-layout">
          <section className="results-list">
            <article className="result-card active">
              <div className="result-top-row">
                <div>
                  <strong>{confirmedIntent.provider}</strong>
                  <p>Provider ref • {confirmedIntent.provider_reference}</p>
                </div>
                <div className="result-price">{formatPrice(confirmedIntent.amount, confirmedIntent.currency)}</div>
              </div>
              <div className="result-detail-grid">
                <div>
                  <span className="field-caption">Durum</span>
                  <strong>{confirmedIntent.status}</strong>
                </div>
                <div>
                  <span className="field-caption">Sepet</span>
                  <strong>{confirmedIntent.cart_id}</strong>
                </div>
                <div>
                  <span className="field-caption">Item sayisi</span>
                  <strong>{confirmedIntent.items.length}</strong>
                </div>
                <div>
                  <span className="field-caption">Checkout yolu</span>
                  <strong>{confirmedIntent.checkout_url}</strong>
                </div>
                <div>
                  <span className="field-caption">Iletisim</span>
                  <strong>{confirmedIntent.contact.email}</strong>
                </div>
                <div>
                  <span className="field-caption">Yolcu sayisi</span>
                  <strong>{confirmedIntent.travelers.length}</strong>
                </div>
              </div>
              <div className="selection-note">
                {confirmedIntent.travelers.map((traveler, index) => (
                  <span key={`${traveler.first_name}-${traveler.last_name}-${index}`}>
                    {index + 1}. {traveler.first_name} {traveler.last_name} • {traveler.traveler_type}
                    {index < confirmedIntent.travelers.length - 1 ? <br /> : null}
                  </span>
                ))}
              </div>
            </article>
          </section>

          <aside className="selection-card">
            <span className="eyebrow">Booking Creation</span>
            <h2>Odeme sonrasi rezervasyon</h2>
            {feedback ? <div className="form-feedback success">{feedback}</div> : null}
            {error ? <div className="form-feedback error">{error}</div> : null}
            {booking ? (
              <div className="selection-grid">
                <div>
                  <span>Booking ref</span>
                  <strong>{booking.booking_reference}</strong>
                </div>
                <div>
                  <span>Durum</span>
                  <strong>{booking.status}</strong>
                </div>
                <div>
                  <span>Toplam</span>
                  <strong>{formatPrice(booking.total_amount, booking.currency)}</strong>
                </div>
                <div>
                  <span>Item</span>
                  <strong>{booking.item_count}</strong>
                </div>
              </div>
            ) : (
              <div className="selection-note">
                Bu adimda payment intent `paid` durumuna gecip booking-service uzerinde rezervasyon acilir.
              </div>
            )}
            <button className="primary-action selection-action" disabled={isProcessing} onClick={handleConfirmAndCreateBooking} type="button">
              {isProcessing ? "Isleniyor..." : "Odemeyi tamamlandi varsay"}
            </button>
            {booking ? (
              <div className="selection-action-grid">
                <Link className="ghost-action selection-action" href={`/bookings/${booking.booking_reference}`}>
                  Rezervasyon detayina git
                </Link>
                <Link className="ghost-action selection-action" href="/account">
                  Hesabim ekranina git
                </Link>
              </div>
            ) : null}
          </aside>
        </div>
      </section>
    </main>
  );
}
