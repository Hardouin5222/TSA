import Link from "next/link";

import { CompassIcon } from "@/components/ui/icons";

type AuthShellProps = {
  title: string;
  description: string;
  helperLabel: string;
  helperHref: string;
  helperText: string;
  children: React.ReactNode;
};

export function AuthShell({
  title,
  description,
  helperLabel,
  helperHref,
  helperText,
  children,
}: AuthShellProps) {
  return (
    <main className="auth-page-shell">
      <section className="auth-hero-card">
        <div className="auth-brand-row">
          <Link className="brand-lockup" href="/">
            <div className="brand-badge">
              <CompassIcon />
            </div>
            <div className="brand-text">
              <strong>Travel Super App</strong>
              <span>Fast booking, trusted checkout, mobile-first flow</span>
            </div>
          </Link>
          <Link className="auth-back-link" href="/">
            Ana sayfaya don
          </Link>
        </div>

        <div className="auth-copy-grid">
          <div className="auth-copy">
            <span className="eyebrow">Customer Access</span>
            <h1>{title}</h1>
            <p>{description}</p>

            <div className="auth-point-list">
              <article>
                <strong>3 adim mantigi</strong>
                <span>Hesaba gir, urun sec, odemeye gec.</span>
              </article>
              <article>
                <strong>Tek hesap, coklu dikey</strong>
                <span>Ucak, otel, arac ve paket akislarini tek panelde topla.</span>
              </article>
              <article>
                <strong>Guvenli temel</strong>
                <span>JWT, refresh token, role ve audit log omurgasi hazir.</span>
              </article>
            </div>
          </div>

          <div className="auth-form-card">
            {children}
            <p className="auth-helper-row">
              {helperLabel} <Link href={helperHref}>{helperText}</Link>
            </p>
          </div>
        </div>
      </section>
    </main>
  );
}
