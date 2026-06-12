"use client";

import Link from "next/link";
import { useMemo, useState } from "react";

import { apiRequest } from "@/lib/api";
import { getSession } from "@/lib/auth";
import { getOrCreateGuestSessionId } from "@/lib/guest-session";
import type { CartEnvelope } from "@/types/cart";
import type { FlightOffer, FlightSearchEnvelope } from "@/types/flights";

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
  const [cartState, setCartState] = useState<CartEnvelope["data"] | null>(null);
  const [cartFeedback, setCartFeedback] = useState<string | null>(null);
  const [cartError, setCartError] = useState<string | null>(null);
  const [isAddingToCart, setIsAddingToCart] = useState(false);

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

  async function handleAddToCart(offer: FlightOffer) {
    setIsAddingToCart(true);
    setCartError(null);
    setCartFeedback(null);

    try {
      const session = getSession();
      const guestSessionId = session ? null : getOrCreateGuestSessionId();

      const payload = await apiRequest<CartEnvelope>("/api/cart/items/flight", {
        method: "POST",
        token: session?.tokens.access_token,
        body: {
          offer,
          guest_session_id: guestSessionId,
        },
      });

      setCartState(payload.data);
      setCartFeedback(payload.message);
      setSelectedOfferId(offer.id);
    } catch (requestError) {
      setCartError(requestError instanceof Error ? requestError.message : "Cart request failed");
    } finally {
      setIsAddingToCart(false);
    }
  }

  return (
    <section className="results-shell">
      <div className="results-route-bar">
        <div className="results-route-main">
          <strong>{data.route_label}</strong>
          <span>{visibleOffers.length} teklif</span>
        </div>
        <div className="results-route-meta">
          <span>{directOnly ? "Direkt filtre acik" : "Tum ucuslar"}</span>
          <span>
            {sortMode === "recommended"
              ? "Onerilen"
              : sortMode === "price"
                ? "En dusuk fiyat"
                : sortMode === "duration"
                  ? "En kisa sure"
                  : "En erken kalkis"}
          </span>
        </div>
      </div>

      <div className="results-layout flight-results-grid">
        <aside className="results-filter-panel">
          <div className="results-filter-card">
            <span className="eyebrow">Filtreler</span>
            <h2>Hizli secim</h2>

            <label className="filter-toggle">
              <input checked={directOnly} onChange={() => setDirectOnly((value) => !value)} type="checkbox" />
              <span>Sadece direkt ucuslar</span>
            </label>

            <div className="results-filter-stack">
              <strong>Siralama</strong>
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

            <div className="results-filter-note">
              Turna benzeri sade akista filtreleri solda topluyoruz; karar alanini ise teklif kartlarina birakiyoruz.
            </div>
          </div>
        </aside>

        <div className="results-main-column">
          <div className="results-header-card compact">
            <span className="eyebrow">Flight Search</span>
            <h1>Sec ve devam et</h1>
            <p>Fiyat, sure ve paket netligi bir arada. Gorsel gurultuyu azaltip satin alma kararini hizlandiriyoruz.</p>
          </div>

          <div className="results-list compact-results">
            {visibleOffers.map((offer) => {
              const isActive = offer.id === selectedOffer?.id;

              return (
                <article className={`result-card offer-card${isActive ? " active" : ""}`} key={offer.id}>
                  <div className="offer-card-main">
                    <div className="offer-card-airline">
                      <strong>
                        {offer.airline_name} <span>{offer.airline_code}</span>
                      </strong>
                      <p>
                        {offer.provider} • {offer.cabin_class} • {offer.fare_family}
                      </p>
                    </div>

                    <div className="offer-card-timeline">
                      <div>
                        <span>Kalkis</span>
                        <strong>{formatTime(offer.departure_at)}</strong>
                        <p>{offer.origin}</p>
                      </div>
                      <div className="offer-card-center">
                        <span>{offer.duration_minutes} dk</span>
                        <div className="timeline-line" />
                        <p>{offer.stop_count === 0 ? "Direkt ucus" : `${offer.stop_count} aktarma`}</p>
                      </div>
                      <div>
                        <span>Varis</span>
                        <strong>{formatTime(offer.arrival_at)}</strong>
                        <p>{offer.destination}</p>
                      </div>
                    </div>

                    <div className="offer-chip-row">
                      <span>{offer.baggage_summary}</span>
                      <span>{offer.cancellation_policy}</span>
                      <span>{offer.package_score}/100 paket skoru</span>
                    </div>
                  </div>

                  <div className="offer-card-side">
                    <div className="result-price">{formatPrice(offer.price_amount, offer.price_currency)}</div>
                    <div className="offer-side-note">{offer.stop_count === 0 ? "En kolay secenek" : "Alternatif rota"}</div>
                    <button className="primary-action compact" onClick={() => handleAddToCart(offer)} type="button">
                      {isAddingToCart && isActive ? "Ekleniyor..." : "Sepete ekle"}
                    </button>
                    <button className="ghost-action compact" onClick={() => setSelectedOfferId(offer.id)} type="button">
                      {isActive ? "Secili" : "Detayi ac"}
                    </button>
                  </div>
                </article>
              );
            })}
          </div>
        </div>

        <aside className="selection-card results-selection-card">
          <span className="eyebrow">Secili teklif</span>
          <h2>Karar ozeti</h2>
          {selectedOffer ? (
            <>
              <div className="selection-summary">
                <strong>{selectedOffer.airline_name}</strong>
                <span>
                  {selectedOffer.origin} → {selectedOffer.destination}
                </span>
              </div>

              <div className="selection-grid compact-grid">
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
                  <span>Bagaj</span>
                  <strong>{selectedOffer.baggage_summary}</strong>
                </div>
              </div>

              {cartFeedback ? <div className="form-feedback success">{cartFeedback}</div> : null}
              {cartError ? <div className="form-feedback error">{cartError}</div> : null}

              {cartState ? (
                <div className="selection-note">
                  Aktif sepet: {cartState.items.length} urun • toplam {formatPrice(cartState.total_amount, cartState.currency)}
                </div>
              ) : (
                <div className="selection-note">
                  Bu panel sadece satin alma kararini destekler. Sonraki adimda yolcu ve odeme bilgilerine gecilir.
                </div>
              )}

              <div className="selection-action-grid">
                <button className="primary-action selection-action" onClick={() => handleAddToCart(selectedOffer)} type="button">
                  {isAddingToCart ? "Sepete ekleniyor..." : "Secili teklifi sepete ekle"}
                </button>
                <Link className="ghost-action selection-action" href="/cart">
                  Sepete git
                </Link>
              </div>
            </>
          ) : (
            <p>Uygun sonuc bulunamadi. Filtreleri gevsetip tekrar deneyebilirsin.</p>
          )}
        </aside>
      </div>
    </section>
  );
}
