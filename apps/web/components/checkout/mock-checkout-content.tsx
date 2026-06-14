"use client";

import Link from "next/link";
import { useMemo, useState } from "react";

import { apiRequest } from "@/lib/api";
import { getSession } from "@/lib/auth";
import { getProductSummary } from "@/lib/product-summary";
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
  const productSummary = getProductSummary(primaryItem ?? null);
  const totalPrice = useMemo(
    () => confirmedIntent.items.reduce((sum, item) => sum + item.unit_price * item.quantity, 0),
    [confirmedIntent.items],
  );
  const hasSeatPreference = Boolean(confirmedIntent.special_requests?.seat_preference);
  const hasMealPreference = Boolean(confirmedIntent.special_requests?.meal_preference);
  const hasSupportNote = Boolean(confirmedIntent.special_requests?.accessibility_note);

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
                  <input placeholder="Promosyon kodu giriniz" readOnly value="" />
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

              {hasSeatPreference || hasMealPreference || hasSupportNote ? (
                <div className="turna-inline-note">
                  {hasSeatPreference ? (
                    <>
                      Koltuk: {confirmedIntent.special_requests?.seat_preference}
                      <br />
                    </>
                  ) : null}
                  {hasMealPreference ? (
                    <>
                      Yemek: {confirmedIntent.special_requests?.meal_preference}
                      <br />
                    </>
                  ) : null}
                  {hasSupportNote ? <>Destek notu: {confirmedIntent.special_requests?.accessibility_note}</> : null}
                </div>
              ) : null}
            </section>
          </div>

          <aside className="turna-summary-panel">
            <section className="turna-summary-card">
              <div className="turna-summary-head">
                <strong>Gidis</strong>
              </div>

              {productSummary?.timeline ? (
                <div className="turna-itinerary-block">
                  <div className="turna-itinerary-row">
                    <div>
                      <strong>{productSummary.timeline.leftTime}</strong>
                      <span>{productSummary.timeline.leftLabel}</span>
                    </div>
                    <div className="turna-itinerary-middle">
                      <span>{productSummary.timeline.middleLabel}</span>
                      <p>{productSummary.timeline.middleSubLabel}</p>
                    </div>
                    <div>
                      <strong>{productSummary.timeline.rightTime}</strong>
                      <span>{productSummary.timeline.rightLabel}</span>
                    </div>
                  </div>

                  <div className="turna-summary-meta">
                    <span>{productSummary.title}</span>
                    <span>{productSummary.subtitle}</span>
                  </div>
                </div>
              ) : productSummary ? (
                <div className="turna-inline-grid">
                  <div>
                    <span>Urun</span>
                    <strong>{productSummary.title}</strong>
                  </div>
                  <div>
                    <span>Detay</span>
                    <strong>{productSummary.subtitle || "-"}</strong>
                  </div>
                </div>
              ) : null}
            </section>

            <section className="turna-summary-card">
              <div className="turna-summary-head">
                <strong>Odeme detayi</strong>
              </div>

              <div className="turna-summary-list">
                <div>
                  <span>Urunler ({confirmedIntent.travelers.length} yolcu)</span>
                  <strong>{formatPrice(totalPrice, confirmedIntent.currency)}</strong>
                </div>
                <div>
                  <span>{productSummary?.meta[0]?.label || "Detay"}</span>
                  <strong>{productSummary?.meta[0]?.value || "-"}</strong>
                </div>
                <div>
                  <span>Secili urun</span>
                  <strong>{productSummary?.title || "-"}</strong>
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
