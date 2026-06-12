"use client";

import Link from "next/link";
import { useState } from "react";

import { apiRequest } from "@/lib/api";
import type { BookingDetailEnvelope } from "@/types/booking";
import type { NotificationDetailEnvelope, NotificationDispatchEnvelope } from "@/types/notification";

function formatPrice(value: number, currency: string) {
  return new Intl.NumberFormat("tr-TR", {
    style: "currency",
    currency,
    maximumFractionDigits: 0,
  }).format(value);
}

function formatDate(value: string) {
  return new Intl.DateTimeFormat("tr-TR", {
    day: "2-digit",
    month: "long",
    year: "numeric",
    hour: "2-digit",
    minute: "2-digit",
  }).format(new Date(value));
}

export function BookingDetailContent({
  booking,
  notification,
}: {
  booking: BookingDetailEnvelope["data"];
  notification: NotificationDetailEnvelope["data"] | null;
}) {
  const [notificationState, setNotificationState] = useState(notification);
  const [notificationFeedback, setNotificationFeedback] = useState<string | null>(null);
  const [notificationError, setNotificationError] = useState<string | null>(null);
  const [isDispatching, setIsDispatching] = useState(false);

  async function handleMockDispatch() {
    if (!notificationState) {
      return;
    }

    setIsDispatching(true);
    setNotificationFeedback(null);
    setNotificationError(null);

    try {
      const payload = await apiRequest<NotificationDispatchEnvelope>(
        `/api/notifications/${notificationState.notification_id}/dispatch-mock`,
        { method: "POST" },
      );

      setNotificationState((current) =>
        current
          ? {
              ...current,
              status: payload.data.status,
              sent_at: payload.data.sent_at,
              provider_reference: payload.data.provider_reference,
            }
          : current,
      );
      setNotificationFeedback(payload.message);
    } catch (requestError) {
      setNotificationError(requestError instanceof Error ? requestError.message : "Notification dispatch failed");
    } finally {
      setIsDispatching(false);
    }
  }

  return (
    <main className="cart-page-shell">
      <div className="results-breadcrumb">
        <Link href="/">Ana sayfaya don</Link>
        <span>/</span>
        <Link href="/account">Hesabim</Link>
        <span>/</span>
        <span>Rezervasyon</span>
      </div>

      <section className="results-shell">
        <div className="checkout-stepper">
          <div className="checkout-step is-complete">
            <span>1</span>
            <strong>Ucus secimi</strong>
          </div>
          <div className="checkout-step is-complete">
            <span>2</span>
            <strong>Yolcu bilgileri</strong>
          </div>
          <div className="checkout-step is-complete">
            <span>3</span>
            <strong>Rezervasyon tamam</strong>
          </div>
        </div>

        <div className="results-header-card compact">
          <span className="eyebrow">Booking Detail</span>
          <h1>{booking.booking_reference}</h1>
          <p>Rezervasyon artik olustu. Bu ekranda sadece kullanicinin tekrar bakacagi ana ozet ve destek bilgileri kalir.</p>
        </div>

        <div className="results-layout checkout-layout">
          <section className="results-list checkout-main">
            {booking.items.map((item) => (
              <article className="checkout-itinerary-card" key={item.id}>
                <div className="checkout-itinerary-header">
                  <div>
                    <span className="eyebrow">Urun</span>
                    <h2>{item.title}</h2>
                    <p>
                      {item.item_type} • ref {item.reference_id}
                    </p>
                  </div>
                  <div className="checkout-price-pill">{formatPrice(item.unit_price, item.currency)}</div>
                </div>

                <div className="checkout-itinerary-grid">
                  <div>
                    <span>Adet</span>
                    <strong>{item.quantity}</strong>
                  </div>
                  <div>
                    <span>Para birimi</span>
                    <strong>{item.currency}</strong>
                  </div>
                  <div>
                    <span>Urun tipi</span>
                    <strong>{item.item_type}</strong>
                  </div>
                  <div>
                    <span>Kaynak ref</span>
                    <strong>{item.reference_id}</strong>
                  </div>
                </div>
              </article>
            ))}
          </section>

          <aside className="selection-card checkout-summary-card">
            <span className="eyebrow">Booking summary</span>
            <h2>Rezervasyon ozeti</h2>

            <div className="selection-grid compact-grid">
              <div>
                <span>Durum</span>
                <strong>{booking.status}</strong>
              </div>
              <div>
                <span>Toplam</span>
                <strong>{formatPrice(booking.total_amount, booking.currency)}</strong>
              </div>
              <div>
                <span>Olusma zamani</span>
                <strong>{formatDate(booking.created_at)}</strong>
              </div>
              <div>
                <span>Urun sayisi</span>
                <strong>{booking.item_count}</strong>
              </div>
            </div>

            <div className="selection-note">
              Provider ref: {booking.provider_reference}
              <br />
              Cart id: {booking.cart_id}
            </div>

            {booking.contact ? (
              <div className="selection-grid compact-grid">
                <div>
                  <span>Iletisim e-postasi</span>
                  <strong>{booking.contact.email}</strong>
                </div>
                <div>
                  <span>Iletisim telefonu</span>
                  <strong>{booking.contact.phone}</strong>
                </div>
              </div>
            ) : null}

            {booking.travelers.length > 0 ? (
              <details className="checkout-disclosure">
                <summary>Yolcu bilgileri</summary>
                <div className="checkout-disclosure-body">
                  <div className="selection-note">
                    {booking.travelers.map((traveler, index) => (
                      <span key={`${traveler.first_name}-${traveler.last_name}-${index}`}>
                        Yolcu {index + 1}: {traveler.first_name} {traveler.last_name} • {traveler.traveler_type} • {traveler.birth_date}
                        {index < booking.travelers.length - 1 ? <br /> : null}
                      </span>
                    ))}
                  </div>
                </div>
              </details>
            ) : null}

            {booking.special_requests &&
            (booking.special_requests.seat_preference ||
              booking.special_requests.meal_preference ||
              booking.special_requests.accessibility_note) ? (
              <details className="checkout-disclosure">
                <summary>Ozel istekler</summary>
                <div className="checkout-disclosure-body">
                  <div className="selection-grid compact-grid">
                    <div>
                      <span>Koltuk</span>
                      <strong>{booking.special_requests.seat_preference || "-"}</strong>
                    </div>
                    <div>
                      <span>Yemek</span>
                      <strong>{booking.special_requests.meal_preference || "-"}</strong>
                    </div>
                    <div>
                      <span>Destek notu</span>
                      <strong>{booking.special_requests.accessibility_note || "-"}</strong>
                    </div>
                  </div>
                </div>
              </details>
            ) : null}

            {booking.billing_details ? (
              <details className="checkout-disclosure">
                <summary>Fatura bilgileri</summary>
                <div className="checkout-disclosure-body">
                  <div className="selection-grid compact-grid">
                    <div>
                      <span>Fatura tipi</span>
                      <strong>{booking.billing_details.invoice_type === "company" ? "Sirket" : "Bireysel"}</strong>
                    </div>
                    <div>
                      <span>Unvan</span>
                      <strong>{booking.billing_details.full_name}</strong>
                    </div>
                    <div>
                      <span>Sehir / Ulke</span>
                      <strong>
                        {booking.billing_details.city} / {booking.billing_details.country}
                      </strong>
                    </div>
                    <div>
                      <span>Vergi</span>
                      <strong>{booking.billing_details.tax_number || "-"}</strong>
                    </div>
                  </div>
                  <div className="selection-note">{booking.billing_details.address_line}</div>
                </div>
              </details>
            ) : null}

            <div className="selection-grid compact-grid">
              <div>
                <span>Bildirim</span>
                <strong>{notificationState?.status || "hazir degil"}</strong>
              </div>
              <div>
                <span>Kanal</span>
                <strong>{notificationState?.channel || "-"}</strong>
              </div>
              <div>
                <span>Alici</span>
                <strong>{notificationState?.recipient_email || notificationState?.recipient_phone || "-"}</strong>
              </div>
              <div>
                <span>Template</span>
                <strong>{notificationState?.template_code || "-"}</strong>
              </div>
            </div>

            {notificationState?.content_preview ? (
              <div className="selection-note">{notificationState.content_preview}</div>
            ) : null}
            {notificationState?.subject ? (
              <div className="selection-note">
                Konu: {notificationState.subject}
                {notificationState.sent_at ? (
                  <>
                    <br />
                    Gonderim zamani: {formatDate(notificationState.sent_at)}
                  </>
                ) : null}
              </div>
            ) : null}
            {notificationState?.text_body ? (
              <details className="checkout-disclosure">
                <summary>Bildirim metni</summary>
                <div className="checkout-disclosure-body">
                  <div className="selection-note" style={{ whiteSpace: "pre-line" }}>
                    {notificationState.text_body}
                  </div>
                </div>
              </details>
            ) : null}

            {notificationFeedback ? <div className="form-feedback success">{notificationFeedback}</div> : null}
            {notificationError ? <div className="form-feedback error">{notificationError}</div> : null}

            {notificationState && notificationState.status !== "sent" ? (
              <button className="ghost-action selection-action" disabled={isDispatching} onClick={handleMockDispatch} type="button">
                {isDispatching ? "Bildirim gonderiliyor..." : "Mock email gonder"}
              </button>
            ) : null}

            <div className="selection-action-grid">
              <Link className="primary-action selection-action" href="/account">
                Rezervasyonlarim ekranina git
              </Link>
              <Link className="ghost-action selection-action" href="/">
                Yeni arama baslat
              </Link>
            </div>
          </aside>
        </div>
      </section>
    </main>
  );
}
