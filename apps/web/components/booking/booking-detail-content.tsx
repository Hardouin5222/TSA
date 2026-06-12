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
        <div className="results-header-card">
          <span className="eyebrow">Booking Detail</span>
          <h1>{booking.booking_reference}</h1>
          <p>
            Rezervasyon olusturuldu. Bu ekran kullaniciya referans, durum, urun kirilimi ve toplam tutari tek
            yerde gosterir.
          </p>
        </div>

        <div className="results-layout">
          <section className="results-list">
            {booking.items.map((item) => (
              <article className="result-card active" key={item.id}>
                <div className="result-top-row">
                  <div>
                    <strong>{item.title}</strong>
                    <p>
                      {item.item_type} • ref {item.reference_id}
                    </p>
                  </div>
                  <div className="result-price">{formatPrice(item.unit_price, item.currency)}</div>
                </div>

                <div className="result-detail-grid">
                  <div>
                    <span className="field-caption">Adet</span>
                    <strong>{item.quantity}</strong>
                  </div>
                  <div>
                    <span className="field-caption">Para birimi</span>
                    <strong>{item.currency}</strong>
                  </div>
                  <div>
                    <span className="field-caption">Urun tipi</span>
                    <strong>{item.item_type}</strong>
                  </div>
                  <div>
                    <span className="field-caption">Kaynak ref</span>
                    <strong>{item.reference_id}</strong>
                  </div>
                </div>
              </article>
            ))}
          </section>

          <aside className="selection-card">
            <span className="eyebrow">Booking Summary</span>
            <h2>Rezervasyon ozeti</h2>
            {booking.special_requests &&
            (booking.special_requests.seat_preference ||
              booking.special_requests.meal_preference ||
              booking.special_requests.accessibility_note) ? (
              <div className="selection-grid">
                <div>
                  <span>Koltuk tercihi</span>
                  <strong>{booking.special_requests.seat_preference || "-"}</strong>
                </div>
                <div>
                  <span>Yemek tercihi</span>
                  <strong>{booking.special_requests.meal_preference || "-"}</strong>
                </div>
                <div>
                  <span>Destek notu</span>
                  <strong>{booking.special_requests.accessibility_note || "-"}</strong>
                </div>
              </div>
            ) : null}
            {booking.billing_details ? (
              <>
                <div className="selection-grid">
                  <div>
                    <span>Fatura tipi</span>
                    <strong>{booking.billing_details.invoice_type === "company" ? "Sirket" : "Bireysel"}</strong>
                  </div>
                  <div>
                    <span>Fatura unvani</span>
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
              </>
            ) : null}
            <div className="selection-grid">
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
              <div className="selection-grid">
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
              <div className="selection-note">
                {booking.travelers.map((traveler, index) => (
                  <span key={`${traveler.first_name}-${traveler.last_name}-${index}`}>
                    Yolcu {index + 1}: {traveler.first_name} {traveler.last_name} • {traveler.traveler_type} •{" "}
                    {traveler.birth_date}
                    {index < booking.travelers.length - 1 ? <br /> : null}
                  </span>
                ))}
              </div>
            ) : null}
            <div className="selection-grid">
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
              <div className="selection-note" style={{ whiteSpace: "pre-line" }}>
                {notificationState.text_body}
              </div>
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
