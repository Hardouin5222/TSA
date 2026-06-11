"use client";

import { useMemo, useState } from "react";

import type { FlightSearchEnvelope } from "@/types/flights";

function formatTime(value: string) {
  const date = new Date(value);
  return new Intl.DateTimeFormat("tr-TR", {
    hour: "2-digit",
    minute: "2-digit",
  }).format(date);
}

function formatPrice(value: number, currency: string) {
  return new Intl.NumberFormat("tr-TR", {
    style: "currency",
    currency,
    maximumFractionDigits: 0,
  }).format(value);
}

type SortMode = "recommended" | "price" | "duration" | "departure";

export function ResultsList({ data }: { data: FlightSearchEnvelope["data"] }) {
  const [sortMode, setSortMode] = useState<SortMode>("recommended");
  const [directOnly, setDirectOnly] = useState(false);
  const [selectedOfferId, setSelectedOfferId] = useState<string | null>(data.offers[0]?.id ?? null);

  const visibleOffers = useMemo(() => {
    let offers = [...data.offers];

    if (directOnly) {
      offers = offers.filter((offer) => offer.stop_count === 0);
    }

    offers.sort((left, right) => {
      if (sortMode === "price") {
        return left.price_amount - right.price_amount;
      }
      if (sortMode === "duration") {
        return left.duration_minutes - right.duration_minutes;
      }
      if (sortMode === "departure") {
        return left.departure_at.localeCompare(right.departure_at);
      }
      return right.package_score - left.package_score;
    });

    return offers;
  }, [data.offers, directOnly, sortMode]);

  const selectedOffer =
    visibleOffers.find((offer) => offer.id === selectedOfferId) ??
    data.offers.find((offer) => offer.id === selectedOfferId) ??
    visibleOffers[0] ??
    null;

  return (
    <section className="results-shell">
      <div className="results-header-card">
        <span className="eyebrow">Flight Search Foundation</span>
        <h1>{data.route_label}</h1>
        <p>
          Bu ekran bugunden normalize flight offers gosterecek sekilde hazir. Yarin ayni kontrata Duffel,
          Travelfusion ve Mystifly adapterleri baglanabilecek.
        </p>
      </div>

      <div className="results-controls-card">
        <div className="results-control-group">
          <label className="filter-toggle">
            <input checked={directOnly} onChange={() => setDirectOnly((value) => !value)} type="checkbox" />
            <span>Sadece direkt ucuslar</span>
          </label>
        </div>

        <div className="results-control-group">
          <span className="field-caption">Siralama</span>
          <div className="sort-pill-row">
            <button className={sortMode === "recommended" ? "is-selected" : ""} onClick={() => setSortMode("recommended")} type="button">
              Onerilen
            </button>
            <button className={sortMode === "price" ? "is-selected" : ""} onClick={() => setSortMode("price")} type="button">
              Fiyat
            </button>
            <button className={sortMode === "duration" ? "is-selected" : ""} onClick={() => setSortMode("duration")} type="button">
              Sure
            </button>
            <button className={sortMode === "departure" ? "is-selected" : ""} onClick={() => setSortMode("departure")} type="button">
              Kalkis
            </button>
          </div>
        </div>
      </div>

      <div className="results-layout">
        <div className="results-list">
          {visibleOffers.map((offer) => {
            const isActive = offer.id === selectedOffer?.id;

            return (
              <article className={`result-card${isActive ? " active" : ""}`} key={offer.id}>
                <div className="result-top-row">
                  <div>
                    <strong>
                      {offer.airline_name} <span>{offer.airline_code}</span>
                    </strong>
                    <p>
                      {offer.provider} uzerinden sunuluyor • {offer.cabin_class} • {offer.fare_family}
                    </p>
                  </div>
                  <div className="result-price">{formatPrice(offer.price_amount, offer.price_currency)}</div>
                </div>

                <div className="result-timeline">
                  <div>
                    <span>Kalkis</span>
                    <strong>{formatTime(offer.departure_at)}</strong>
                    <p>{offer.origin}</p>
                  </div>
                  <div className="timeline-center">
                    <span>{offer.duration_minutes} dk</span>
                    <div className="timeline-line" />
                    <p>{offer.stop_count === 0 ? "Direkt" : `${offer.stop_count} aktarma`}</p>
                  </div>
                  <div>
                    <span>Varis</span>
                    <strong>{formatTime(offer.arrival_at)}</strong>
                    <p>{offer.destination}</p>
                  </div>
                </div>

                <div className="result-detail-grid">
                  <div>
                    <span className="field-caption">Bagaj</span>
                    <strong>{offer.baggage_summary}</strong>
                  </div>
                  <div>
                    <span className="field-caption">Iptal politikasi</span>
                    <strong>{offer.cancellation_policy}</strong>
                  </div>
                  <div>
                    <span className="field-caption">Koltuk araligi</span>
                    <strong>{offer.seat_pitch}</strong>
                  </div>
                  <div>
                    <span className="field-caption">Paket skoru</span>
                    <strong>{offer.package_score}/100</strong>
                  </div>
                </div>

                <div className="result-bottom-row">
                  <div className="result-tags">
                    {offer.tags.map((tag) => (
                      <span key={tag}>{tag}</span>
                    ))}
                  </div>
                  <button className="primary-action compact" onClick={() => setSelectedOfferId(offer.id)} type="button">
                    {isActive ? "Secildi" : "Bu teklifi sec"}
                  </button>
                </div>
              </article>
            );
          })}
        </div>

        <aside className="selection-card">
          <span className="eyebrow">Cart Foundation</span>
          <h2>Secili teklif ozeti</h2>
          {selectedOffer ? (
            <>
              <div className="selection-summary">
                <strong>{selectedOffer.airline_name}</strong>
                <span>
                  {selectedOffer.origin} → {selectedOffer.destination}
                </span>
              </div>
              <div className="selection-grid">
                <div>
                  <span>Fiyat</span>
                  <strong>{formatPrice(selectedOffer.price_amount, selectedOffer.price_currency)}</strong>
                </div>
                <div>
                  <span>Sure</span>
                  <strong>{selectedOffer.duration_minutes} dk</strong>
                </div>
                <div>
                  <span>Fare family</span>
                  <strong>{selectedOffer.fare_family}</strong>
                </div>
                <div>
                  <span>Paket uyumu</span>
                  <strong>{selectedOffer.package_score}/100</strong>
                </div>
              </div>
              <div className="selection-note">
                Bu kart bir sonraki sprintte gercek `cart-service` ve checkout akisina baglanacak.
              </div>
              <button className="primary-action selection-action" type="button">
                Sepete ekle foundation
              </button>
            </>
          ) : (
            <p>Uygun sonuc bulunamadi. Filtreleri gevsetip tekrar deneyebilirsin.</p>
          )}
        </aside>
      </div>
    </section>
  );
}
