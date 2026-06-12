"use client";

import Link from "next/link";
import { useEffect, useState } from "react";
import { useRouter } from "next/navigation";

import { apiRequest } from "@/lib/api";
import { clearSession, getSession, type StoredSession } from "@/lib/auth";
import { ShieldIcon, SparkIcon } from "@/components/ui/icons";
import type { BookingListEnvelope } from "@/types/booking";
import type { NotificationListEnvelope } from "@/types/notification";

type ProfileEnvelope = {
  success: boolean;
  message: string;
  data: {
    id: string;
    email: string;
    first_name: string;
    last_name: string;
    phone_number: string | null;
    status: string;
    is_email_verified: boolean;
    created_at: string;
  };
};

export function AccountPanel() {
  const router = useRouter();
  const [session, setSession] = useState<StoredSession | null>(null);
  const [profileState, setProfileState] = useState<ProfileEnvelope["data"] | null>(null);
  const [bookings, setBookings] = useState<BookingListEnvelope["data"]["bookings"]>([]);
  const [notifications, setNotifications] = useState<NotificationListEnvelope["data"]["notifications"]>([]);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    const storedSession = getSession();
    setSession(storedSession);

    if (!storedSession) {
      return;
    }

    apiRequest<ProfileEnvelope>("/api/users/me", {
      token: storedSession.tokens.access_token,
    })
      .then((payload) => {
        setProfileState(payload.data);
      })
      .catch((requestError) => {
        setError(requestError instanceof Error ? requestError.message : "Profile loading failed");
      });

    apiRequest<BookingListEnvelope>(`/api/bookings?user_id=${storedSession.user.id}`, {
      token: storedSession.tokens.access_token,
    })
      .then((payload) => {
        setBookings(payload.data.bookings);
      })
      .catch((requestError) => {
        setError(requestError instanceof Error ? requestError.message : "Booking list loading failed");
      });

    apiRequest<NotificationListEnvelope>(`/api/notifications?user_id=${storedSession.user.id}`, {
      token: storedSession.tokens.access_token,
    })
      .then((payload) => {
        setNotifications(payload.data.notifications);
      })
      .catch((requestError) => {
        setError(requestError instanceof Error ? requestError.message : "Notification list loading failed");
      });
  }, []);

  function handleLogout() {
    clearSession();
    router.push("/login");
    router.refresh();
  }

  if (!session) {
    return (
      <div className="account-card centered">
        <span className="eyebrow">Session gerekli</span>
        <h1>Bu alan icin once giris yapman gerekiyor.</h1>
        <p>Web auth akisini test etmek icin giris sayfasina gec.</p>
        <div className="auth-cta-row">
          <Link className="primary-action compact" href="/login">
            Giris yap
          </Link>
          <Link className="ghost-action" href="/register">
            Hesap olustur
          </Link>
        </div>
      </div>
    );
  }

  const displayUser = profileState || session.user;

  function getNotificationForBooking(bookingReference: string) {
    return notifications.find((item) => item.booking_reference === bookingReference) || null;
  }

  return (
    <div className="account-layout">
      <section className="account-card">
        <span className="eyebrow">Hesabim</span>
        <h1>
          {displayUser.first_name} {displayUser.last_name}
        </h1>
        <p>Bu panel sonraki sprintte rezervasyonlar, yolcular ve odeme adimlariyla genisleyecek.</p>

        <div className="account-stat-grid">
          <article className="account-stat">
            <strong>{displayUser.email}</strong>
            <span>E-posta hesabi</span>
          </article>
          <article className="account-stat">
            <strong>{displayUser.status}</strong>
            <span>Profil durumu</span>
          </article>
          <article className="account-stat">
            <strong>{displayUser.is_email_verified ? "Dogrulandi" : "Beklemede"}</strong>
            <span>E-posta dogrulamasi</span>
          </article>
          <article className="account-stat">
            <strong>{bookings.length}</strong>
            <span>Toplam rezervasyon</span>
          </article>
        </div>

        {error ? <div className="form-feedback error">{error}</div> : null}

        <div className="results-list">
          {bookings.length > 0 ? (
            bookings.map((booking) => (
              <article className="result-card active" key={booking.booking_reference}>
                <div className="result-top-row">
                  <div>
                    <strong>{booking.primary_item_title}</strong>
                    <p>Ref {booking.booking_reference}</p>
                  </div>
                  <div className="result-price">
                    {new Intl.NumberFormat("tr-TR", {
                      style: "currency",
                      currency: booking.currency,
                      maximumFractionDigits: 0,
                    }).format(booking.total_amount)}
                  </div>
                </div>
                <div className="result-detail-grid">
                  <div>
                    <span className="field-caption">Durum</span>
                    <strong>{booking.status}</strong>
                  </div>
                  <div>
                    <span className="field-caption">Urun</span>
                    <strong>{booking.item_count}</strong>
                  </div>
                  <div>
                    <span className="field-caption">Bildirim</span>
                    <strong>{getNotificationForBooking(booking.booking_reference)?.status || "hazir degil"}</strong>
                  </div>
                  <div>
                    <span className="field-caption">Kanal</span>
                    <strong>{getNotificationForBooking(booking.booking_reference)?.channel || "-"}</strong>
                  </div>
                </div>
                <div className="auth-cta-row">
                  <Link className="ghost-action" href={`/bookings/${booking.booking_reference}`}>
                    Detayi ac
                  </Link>
                </div>
              </article>
            ))
          ) : (
            <div className="account-stat">
              <strong>Henuz rezervasyon yok</strong>
              <span>Ilk rezervasyon olustugunda bu alanda booking referanslari gorunecek.</span>
            </div>
          )}
        </div>

        <div className="auth-cta-row">
          <button className="ghost-action" onClick={handleLogout} type="button">
            Cikis yap
          </button>
          <Link className="primary-action compact" href="/">
            Ana sayfaya don
          </Link>
        </div>
      </section>

      <section className="account-card account-side-card">
        <div className="account-side-item">
          <div className="icon-box light">
            <ShieldIcon />
          </div>
          <div>
            <strong>Auth omurgasi aktif</strong>
            <span>JWT, refresh token ve permission temeli backend ile eslesti.</span>
          </div>
        </div>

        <div className="account-side-item">
          <div className="icon-box light">
            <SparkIcon />
          </div>
          <div>
            <strong>Siradaki hedef</strong>
            <span>Son aramalar, yolcu listesi, odeme yontemleri ve iptal-iletisim adimlari eklenecek.</span>
          </div>
        </div>
      </section>
    </div>
  );
}
