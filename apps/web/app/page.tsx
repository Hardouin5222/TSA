import { BookingTabs } from "@/components/booking-tabs";
import { CrossSellRail } from "@/components/cross-sell-rail";
import { ExperienceHighlights } from "@/components/experience-highlights";
import { Footer } from "@/components/footer";
import { HeroSearch } from "@/components/hero-search";
import { PopularRoutes } from "@/components/popular-routes";
import { TopNav } from "@/components/top-nav";
import { TrustBar } from "@/components/trust-bar";
import Link from "next/link";

export default function HomePage() {
  return (
    <main className="page-shell">
      <section className="hero-panel">
        <div className="hero-glow hero-glow-left" />
        <div className="hero-glow hero-glow-right" />
        <TopNav />
        <div className="hero-copy">
          <span className="eyebrow">Travel Super App MVP</span>
          <h1>Ucustan uca seyahat planlamasi, 3 tik mantigina yakin bir akisla.</h1>
          <p>
            Ucak, otel, arac kiralama ve paketleri tek deneyimde bulusturan; mobilde hizli, masaustunde
            guven veren bir satin alma omurgasi kuruyoruz.
          </p>
          <div className="hero-cta-row">
            <Link className="primary-action compact" href="/register">
              Hesap olustur ve devam et
            </Link>
            <Link className="ghost-action" href="/login">
              Mevcut hesabim var
            </Link>
          </div>
        </div>
        <BookingTabs />
        <HeroSearch />
        <TrustBar />
      </section>

      <section className="content-grid">
        <CrossSellRail />
        <PopularRoutes />
        <ExperienceHighlights />
      </section>

      <Footer />
    </main>
  );
}
