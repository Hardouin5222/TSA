"use client";

import Link from "next/link";
import { useEffect, useState } from "react";
import { useRouter } from "next/navigation";

import { apiRequest } from "@/lib/api";
import { clearSession, getSession, type StoredSession } from "@/lib/auth";
import { ShieldIcon, SparkIcon } from "@/components/ui/icons";

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
        </div>

        {error ? <div className="form-feedback error">{error}</div> : null}

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
            <span>Rezervasyon listesi, son aramalar ve profil ayarlari bu panele eklenecek.</span>
          </div>
        </div>
      </section>
    </div>
  );
}
