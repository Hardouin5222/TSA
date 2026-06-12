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
  const hasSeatPreference = Boolean(booking.special_requests?.seat_preference);
  const hasMealPreference = Boolean(booking.special_requests?.meal_preference);
  const hasSupportNote = Boolean(booking.special_requests?.accessibility_note);

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

      <section className="turna-process-shell">
        <div className="turna-process-bar">
          <div className="turna-process-brand">
            <strong>Travel Super App</strong>
            <span>Booking detail</span>
          </div>

          <div className="turna-process-steps" aria-label="Rezervasyon akisi">
            <div className="turna-process-step is-complete">
              <span>1</span>
              <strong>Ucus secimi</strong>
            </div>
            <div className="turna-process-step is-complete">
              <span>2</span>
              <strong>Yolcu bilgileri</strong>
            </div>
            <div className="turna-process-step is-complete">
              <span>3</span>
              <strong>Tamamlandi</strong>
            </div>
          </div>
        </div>

        <div className="turna-checkout-grid">
          <div className="turna-checkout-main">
            <section className="turna-process-card turna-package-summary-card">
              <div className="turna-card-head">
                <div>
                  <span className="turna-card-label">Booking detail</span>
                  <h1>{booking.booking_reference}</h1>
                </div>
              </div>
              <p className="turna-intro-copy">
                Rezervasyon olustu. Bu ekranda kullanicinin tekrar bakacagi urun, yolcu, bildirim ve toplam ozeti tek
                alanda tutulur.
              </p>
            </section>

            {booking.items.map((item) => (
              <section className="turna-process-card" key={item.id}>
                <div className="turna-section-title">
                  <strong>{item.title}</strong>
                  <span>
                    {item.item_type} / ref {item.reference_id}
                  </span>
                </div>

                <div className="turna-inline-grid">
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
                    <span>Tutar</span>
                    <strong>{formatPrice(item.unit_price, item.currency)}</strong>
                  </div>
                </div>
              </section>
            ))}

            {hasSeatPreference || hasMealPreference || hasSupportNote ? (
              <section className="turna-process-card">
                <div className="turna-section-title">
                  <strong>Ozel istekler</strong>
                  <span>Rezervasyonla birlikte kayda giren tercihler.</span>
                </div>

                <div className="turna-inline-grid">
                  {hasSeatPreference ? (
                    <div>
                      <span>Koltuk tercihi</span>
                      <strong>{booking.special_requests?.seat_preference}</strong>
                    </div>
                  ) : null}
                  {hasMealPreference ? (
                    <div>
                      <span>Yemek tercihi</span>
                      <strong>{booking.special_requests?.meal_preference}</strong>
                    </div>
                  ) : null}
                  {hasSupportNote ? (
                    <div>
                      <span>Destek notu</span>
                      <strong>{booking.special_requests?.accessibility_note}</strong>
                    </div>
                  ) : null}
                </div>
              </section>
            ) : null}
          </div>

          <aside className="turna-summary-panel">
            <section className="turna-summary-card">
              <div className="turna-summary-head">
                <strong>Rezervasyon ozeti</strong>
              </div>

              <div className="turna-summary-list">
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

              {booking.contact ? (
                <div className="turna-inline-grid">
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
                <div className="turna-inline-note">
                  {booking.travelers.map((traveler, index) => (
                    <span key={`${traveler.first_name}-${traveler.last_name}-${index}`}>
                      Yolcu {index + 1}: {traveler.first_name} {traveler.last_name} / {traveler.traveler_type} /{" "}
                      {traveler.birth_date}
                      {index < booking.travelers.length - 1 ? <br /> : null}
                    </span>
                  ))}
                </div>
              ) : null}

              {booking.billing_details ? (
                <div className="turna-inline-grid">
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
              ) : null}

              <div className="turna-inline-note">
                Provider ref: {booking.provider_reference}
                <br />
                Cart id: {booking.cart_id}
              </div>
            </section>

            <section className="turna-summary-card">
              <div className="turna-summary-head">
                <strong>Bildirim</strong>
              </div>

              <div className="turna-inline-grid">
                <div>
                  <span>Durum</span>
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
                <div className="turna-inline-note">{notificationState.content_preview}</div>
              ) : null}

              {notificationState?.subject ? (
                <div className="turna-inline-note">
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
                <details className="turna-collapse-card">
                  <summary>Bildirim metni</summary>
                  <div className="turna-collapse-body">
                    <div className="turna-inline-note" style={{ whiteSpace: "pre-line" }}>
                      {notificationState.text_body}
                    </div>
                  </div>
                </details>
              ) : null}

              {notificationFeedback ? <div className="form-feedback success">{notificationFeedback}</div> : null}
              {notificationError ? <div className="form-feedback error">{notificationError}</div> : null}

              {notificationState && notificationState.status !== "sent" ? (
                <button className="turna-secondary-button" disabled={isDispatching} onClick={handleMockDispatch} type="button">
                  {isDispatching ? "Gonderiliyor..." : "Mock email gonder"}
                </button>
              ) : null}

              <div className="turna-stack-actions">
                <Link className="turna-primary-button" href="/account">
                  Rezervasyonlarim ekranina git
                </Link>
                <Link className="turna-secondary-button" href="/">
                  Yeni arama baslat
                </Link>
              </div>
            </section>
          </aside>
        </div>
      </section>
    </main>
  );
}
