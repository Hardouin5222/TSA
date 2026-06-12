"use client";

import Link from "next/link";
import { useMemo, useState } from "react";

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
  special_requests?: {
    seat_preference?: string | null;
    meal_preference?: string | null;
    accessibility_note?: string | null;
  } | null;
  billing_details: {
    invoice_type: string;
    full_name: string;
    country: string;
    city: string;
    address_line: string;
    company_name?: string | null;
    tax_number?: string | null;
  };
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

function readString(payload: Record<string, unknown>, key: string, fallback = "") {
  const value = payload[key];
  return typeof value === "string" ? value : fallback;
}

function readNumber(payload: Record<string, unknown>, key: string, fallback = 0) {
  const value = payload[key];
  return typeof value === "number" ? value : fallback;
}

function formatTime(value: string | undefined) {
  if (!value) {
    return "--:--";
  }

  return new Intl.DateTimeFormat("tr-TR", {
    hour: "2-digit",
    minute: "2-digit",
  }).format(new Date(value));
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

  const primaryItem = confirmedIntent.items[0];
  const payload = primaryItem?.item_payload ?? {};
  const totalPrice = useMemo(
    () => confirmedIntent.items.reduce((sum, item) => sum + item.unit_price * item.quantity, 0),
    [confirmedIntent.items],
  );

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
          special_requests: confirmed.data.special_requests,
          billing_details: confirmed.data.billing_details,
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

      <section className="turna-process-shell">
        <div className="turna-process-bar">
          <div className="turna-process-brand">
            <strong>Travel Super App</strong>
            <span>Payment step</span>
          </div>

          <div className="turna-process-steps" aria-label="Odeme adimlari">
            <div className="turna-process-step is-complete">
              <span>1</span>
              <strong>Ucus secimi</strong>
            </div>
            <div className="turna-process-step is-complete">
              <span>2</span>
              <strong>Yolcu bilgileri</strong>
            </div>
            <div className="turna-process-step is-active">
              <span>3</span>
              <strong>Odeme</strong>
            </div>
          </div>
        </div>

        <div className="turna-checkout-grid">
          <div className="turna-checkout-main">
            <section className="turna-process-card turna-payment-card">
              <div className="turna-card-head">
                <div>
                  <span className="turna-card-label">Odeme niyeti</span>
                  <h1>Odeme hazirligi</h1>
                </div>
              </div>

              <div className="turna-payment-grid">
                <div className="turna-payment-field">
                  <span>Promosyon Kodu</span>
                  <button type="button">Promosyon kodu giriniz</button>
                </div>
                <div className="turna-payment-field">
                  <span>Fatura Bilgileri</span>
                  <strong>
                    {confirmedIntent.billing_details.invoice_type === "company" ? "Sirket" : "Bireysel"} /{" "}
                    {confirmedIntent.billing_details.full_name}
                  </strong>
                </div>
              </div>

              <div className="turna-card-form-grid">
                <label className="turna-field">
                  <span>Kart Numarasi</span>
                  <input placeholder="0000 0000 0000 0000" readOnly value="4444 4444 4444 4444" />
                </label>
                <label className="turna-field">
                  <span>Taksit Secenekleri</span>
                  <input placeholder="Tek cekim" readOnly value="Tek cekim" />
                </label>
                <label className="turna-field">
                  <span>Son Kullanma Tarihi</span>
                  <input placeholder="AA / YY" readOnly value="12 / 30" />
                </label>
                <label className="turna-field">
                  <span>CVV</span>
                  <input placeholder="000" readOnly value="000" />
                </label>
              </div>

              <div className="turna-inline-note">
                Bu ekran mock seviyede odeme adimini temsil eder. Sonraki entegrasyonda iyzico checkout, callback ve
                failover akisi bu yapinin ustune gelecek.
              </div>
            </section>

            <section className="turna-process-card">
              <div className="turna-section-title">
                <strong>Rezervasyon verisi</strong>
                <span>Bu intent ile birlikte giden temel veriler.</span>
              </div>

              <div className="turna-inline-grid">
                <div>
                  <span>Iletisim</span>
                  <strong>{confirmedIntent.contact.email}</strong>
                </div>
                <div>
                  <span>Telefon</span>
                  <strong>{confirmedIntent.contact.phone}</strong>
                </div>
                <div>
                  <span>Yolcu sayisi</span>
                  <strong>{confirmedIntent.travelers.length}</strong>
                </div>
                <div>
                  <span>Durum</span>
                  <strong>{confirmedIntent.status}</strong>
                </div>
              </div>

              <div className="turna-inline-note">
                {confirmedIntent.travelers.map((traveler, index) => (
                  <span key={`${traveler.first_name}-${traveler.last_name}-${index}`}>
                    {index + 1}. {traveler.first_name} {traveler.last_name} / {traveler.traveler_type} /{" "}
                    {traveler.birth_date}
                    {index < confirmedIntent.travelers.length - 1 ? <br /> : null}
                  </span>
                ))}
              </div>

              {(confirmedIntent.special_requests?.seat_preference ||
                confirmedIntent.special_requests?.meal_preference ||
                confirmedIntent.special_requests?.accessibility_note) ? (
                <div className="turna-inline-note">
                  Koltuk: {confirmedIntent.special_requests?.seat_preference || "-"}
                  <br />
                  Yemek: {confirmedIntent.special_requests?.meal_preference || "-"}
                  <br />
                  Destek notu: {confirmedIntent.special_requests?.accessibility_note || "-"}
                </div>
              ) : null}
            </section>
          </div>

          <aside className="turna-summary-panel">
            <section className="turna-summary-card">
              <div className="turna-summary-head">
                <strong>Gidis</strong>
              </div>

              <div className="turna-itinerary-block">
                <div className="turna-itinerary-row">
                  <div>
                    <strong>{formatTime(readString(payload, "departure_at"))}</strong>
                    <span>{readString(payload, "origin") || "---"}</span>
                  </div>
                  <div className="turna-itinerary-middle">
                    <span>{readNumber(payload, "duration_minutes")} dk</span>
                    <p>{readString(payload, "provider", confirmedIntent.provider)}</p>
                  </div>
                  <div>
                    <strong>{formatTime(readString(payload, "arrival_at"))}</strong>
                    <span>{readString(payload, "destination") || "---"}</span>
                  </div>
                </div>

                <div className="turna-summary-meta">
                  <span>{readString(payload, "airline_name", primaryItem?.title || confirmedIntent.provider)}</span>
                  <span>{readString(payload, "fare_family", "-")}</span>
                </div>
              </div>
            </section>

            <section className="turna-summary-card">
              <div className="turna-summary-head">
                <strong>Odeme detayi</strong>
              </div>

              <div className="turna-summary-list">
                <div>
                  <span>Biletler ({confirmedIntent.travelers.length} yolcu)</span>
                  <strong>{formatPrice(totalPrice, confirmedIntent.currency)}</strong>
                </div>
                <div>
                  <span>Bagaj</span>
                  <strong>{readString(payload, "baggage_summary", "Dahil")}</strong>
                </div>
                <div>
                  <span>Paket</span>
                  <strong>{readString(payload, "fare_family", "-")}</strong>
                </div>
                <div>
                  <span>Toplam</span>
                  <strong>{formatPrice(confirmedIntent.amount, confirmedIntent.currency)}</strong>
                </div>
              </div>

              {feedback ? <div className="form-feedback success">{feedback}</div> : null}
              {error ? <div className="form-feedback error">{error}</div> : null}

              <button className="turna-primary-button" disabled={isProcessing} onClick={handleConfirmAndCreateBooking} type="button">
                {isProcessing ? "Isleniyor..." : "Odemeyi tamamla"}
              </button>

              {booking ? (
                <div className="turna-stack-actions">
                  <Link className="turna-secondary-button" href={`/bookings/${booking.booking_reference}`}>
                    Rezervasyon detayina git
                  </Link>
                  <Link className="turna-secondary-button" href="/account">
                    Hesabim ekranina git
                  </Link>
                </div>
              ) : null}
            </section>
          </aside>
        </div>
      </section>
    </main>
  );
}
