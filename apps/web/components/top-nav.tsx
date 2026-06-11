import Link from "next/link";

import { CompassIcon } from "@/components/ui/icons";

export function TopNav() {
  return (
    <header className="top-nav">
      <div className="brand-lockup">
        <div className="brand-badge">
          <CompassIcon />
        </div>
        <div className="brand-text">
          <strong>Travel Super App</strong>
          <span>Flights, hotels, cars, packages</span>
        </div>
      </div>

      <nav className="top-nav-links" aria-label="Primary">
        <a href="#search">Ara</a>
        <a href="#cross-sell">Firsatlar</a>
        <a href="#routes">Rotalar</a>
        <a href="#experience">Neden biz</a>
      </nav>

      <div className="nav-actions">
        <Link className="ghost-action" href="/login">
          Giris yap
        </Link>
        <Link className="primary-action compact" href="/register">
          Hesap olustur
        </Link>
      </div>
    </header>
  );
}
