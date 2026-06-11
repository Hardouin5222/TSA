"use client";

import Link from "next/link";

import type { BookingDetailEnvelope } from "@/types/booking";

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

export function BookingDetailContent({ booking }: { booking: BookingDetailEnvelope["data"] }) {
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
