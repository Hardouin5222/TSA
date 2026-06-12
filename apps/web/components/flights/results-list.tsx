"use client";

import Link from "next/link";
import { useMemo, useState } from "react";
import { useRouter } from "next/navigation";

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

function formatDateLabel(value: string) {
  const date = new Date(value);
  return new Intl.DateTimeFormat("tr-TR", {
    day: "2-digit",
    month: "short",
    weekday: "short",
  }).format(date);
}

function formatPrice(value: number, currency: string) {
  return new Intl.NumberFormat("tr-TR", {
    style: "currency",
    currency,
    maximumFractionDigits: 0,
  }).format(value);
}

type SortMode = "recommended" | "price" | "duration" | "nonstop";
type FareOption = FlightOffer["fare_options"][number];

function buildOfferFromFareOption(offer: FlightOffer, fareOption: FareOption): FlightOffer {
  return {
    ...offer,
    baggage_summary: `${fareOption.hand_baggage} + ${fareOption.checked_baggage}`,
    fare_family: fareOption.label,
    cancellation_policy: fareOption.refundable
      ? "Esnek iade / degisiklik"
      : fareOption.exchangeable
        ? "Degisiklik hakki var"
        : "Temel paket kurallari",
    price_amount: Number((offer.price_amount + fareOption.price_delta).toFixed(2)),
    selected_fare_option_id: fareOption.id,
    tags: [
      offer.stop_count === 0 ? "Direkt" : `${offer.stop_count} aktarma`,
      fareOption.checked_baggage,
      fareOption.refundable ? "Esnek" : "Standart",
    ],
  };
}

export function ResultsList({ data }: { data: FlightSearchEnvelope["data"] }) {
  const router = useRouter();
  const [sortMode, setSortMode] = useState<SortMode>("price");
  const [directOnly, setDirectOnly] = useState(false);
  const [oneStopOnly, setOneStopOnly] = useState(false);
  const [selectedAirlines, setSelectedAirlines] = useState<string[]>([]);
  const [activeOffer, setActiveOffer] = useState<FlightOffer | null>(null);
  const [selectedFareOptionId, setSelectedFareOptionId] = useState<string | null>(null);
  const [cartState, setCartState] = useState<CartEnvelope["data"] | null>(null);
  const [cartFeedback, setCartFeedback] = useState<string | null>(null);
  const [cartError, setCartError] = useState<string | null>(null);
  const [isAddingToCart, setIsAddingToCart] = useState(false);

  const airlineGroups = useMemo(() => {
    const counts = new Map<string, { name: string; count: number }>();
    for (const offer of data.offers) {
      const current = counts.get(offer.airline_code);
      if (current) {
        current.count += 1;
      } else {
        counts.set(offer.airline_code, { name: offer.airline_name, count: 1 });
      }
    }
    return [...counts.entries()].map(([code, meta]) => ({
      code,
      name: meta.name,
      count: meta.count,
    }));
  }, [data.offers]);

  const visibleOffers = useMemo(() => {
    let offers = [...data.offers];

    if (directOnly) {
      offers = offers.filter((offer) => offer.stop_count === 0);
    }

    if (oneStopOnly) {
      offers = offers.filter((offer) => offer.stop_count === 1);
    }

    if (selectedAirlines.length > 0) {
      offers = offers.filter((offer) => selectedAirlines.includes(offer.airline_code));
    }

    offers.sort((left, right) => {
      if (sortMode === "price") {
        return left.price_amount - right.price_amount;
      }
      if (sortMode === "duration") {
        return left.duration_minutes - right.duration_minutes;
      }
      if (sortMode === "nonstop") {
        return left.stop_count - right.stop_count;
      }
      return right.package_score - left.package_score;
    });

    return offers;
  }, [data.offers, directOnly, oneStopOnly, selectedAirlines, sortMode]);

  const dateTabs = useMemo(() => {
    const firstOffer = data.offers[0];
    const baseDate = new Date(firstOffer?.departure_at ?? `${new Date().toISOString().slice(0, 10)}T08:00:00`);
    return [-1, 0, 1].map((offset) => {
      const next = new Date(baseDate);
      next.setDate(baseDate.getDate() + offset);
      return {
        key: offset,
        label: formatDateLabel(next.toISOString()),
        active: offset === 0,
      };
    });
  }, [data.offers]);

  const selectedCartLabel = cartState
    ? `Sepette ${cartState.items.length} urun var. Toplam ${formatPrice(cartState.total_amount, cartState.currency)}`
    : null;

  function toggleAirline(code: string) {
    setSelectedAirlines((current) =>
      current.includes(code) ? current.filter((item) => item !== code) : [...current, code],
    );
  }

  function openPackageModal(offer: FlightOffer) {
    setActiveOffer(offer);
    setSelectedFareOptionId(offer.selected_fare_option_id || offer.fare_options[0]?.id || null);
  }

  function closePackageModal() {
    setActiveOffer(null);
    setSelectedFareOptionId(null);
  }

  async function handleConfirmFareSelection() {
    if (!activeOffer || !selectedFareOptionId) {
      return;
    }

    const selectedFareOption = activeOffer.fare_options.find((option) => option.id === selectedFareOptionId);
    if (!selectedFareOption) {
      return;
    }

    const offer = buildOfferFromFareOption(activeOffer, selectedFareOption);

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
      setCartFeedback("Secilen ucus paketi sepete eklendi.");
      closePackageModal();
      router.push("/cart");
    } catch (requestError) {
      setCartError(requestError instanceof Error ? requestError.message : "Cart request failed");
    } finally {
      setIsAddingToCart(false);
    }
  }

  const activeFareOption =
    activeOffer?.fare_options.find((option) => option.id === selectedFareOptionId) ?? activeOffer?.fare_options[0] ?? null;

  return (
    <section className="turna-shell">
      <div className="turna-toolbar">
        <div className="turna-brand">
          <button aria-label="Menu" className="turna-menu-button" type="button">
            <span />
            <span />
            <span />
          </button>
          <div className="turna-logo-lockup">
            <strong>Travel Super App</strong>
            <span>Flights</span>
          </div>
        </div>

        <div className="turna-top-links">
          <Link href="/">Ana sayfa</Link>
          <a href="#results">Ucuslar</a>
          <a href="#support">Destek</a>
          <Link className="turna-login-link" href="/login">
            Giris yap
          </Link>
        </div>
      </div>

      <div className="turna-body" id="results">
        <aside className="turna-vertical-nav" aria-hidden="true">
          <span>✈</span>
          <span>🛏</span>
          <span>🚘</span>
          <span>🎫</span>
          <span>📱</span>
        </aside>

        <div className="turna-content">
          <div className="turna-search-strip">
            <div className="turna-search-main">
              <strong>{data.route_label.replace("â†’", "→")}</strong>
              <span>{formatDateLabel(data.offers[0]?.departure_at ?? new Date().toISOString())}</span>
              <span>1 Yolcu</span>
            </div>
            <div className="turna-search-actions">
              <button type="button">Aramayi duzenle</button>
              <button type="button">Gunluk fiyatlar</button>
            </div>
          </div>

          <div className="turna-grid">
            <aside className="turna-filter-column">
              <div className="turna-alarm-card">
                <div>
                  <strong>Fiyat alarmi kur</strong>
                  <span>Fiyat degisirse haber verelim</span>
                </div>
                <div className="turna-switch" />
              </div>

              <div className="turna-filter-card">
                <details className="turna-filter-group" open>
                  <summary>Oneriler</summary>
                  <label className="turna-check-row">
                    <input checked={directOnly} onChange={() => setDirectOnly((value) => !value)} type="checkbox" />
                    <span>Direkt</span>
                  </label>
                  <label className="turna-check-row">
                    <input checked={oneStopOnly} onChange={() => setOneStopOnly((value) => !value)} type="checkbox" />
                    <span>1 aktarma</span>
                  </label>
                </details>

                <details className="turna-filter-group" open>
                  <summary>Hava yolu sirketleri</summary>
                  {airlineGroups.map((airline) => (
                    <label className="turna-check-row" key={airline.code}>
                      <input
                        checked={selectedAirlines.includes(airline.code)}
                        onChange={() => toggleAirline(airline.code)}
                        type="checkbox"
                      />
                      <span>
                        {airline.name} ({airline.count})
                      </span>
                    </label>
                  ))}
                </details>

                <details className="turna-filter-group">
                  <summary>Saat araligi</summary>
                </details>
                <details className="turna-filter-group">
                  <summary>Bagaj</summary>
                </details>
                <details className="turna-filter-group">
                  <summary>Aktarma</summary>
                </details>
                <details className="turna-filter-group">
                  <summary>Kalkis havalimanlari</summary>
                </details>
              </div>
            </aside>

            <div className="turna-results-column">
              <div className="turna-date-tabs">
                {dateTabs.map((tab) => (
                  <button className={tab.active ? "is-active" : ""} key={tab.key} type="button">
                    {tab.label}
                  </button>
                ))}
              </div>

              <div className="turna-sort-tabs">
                <button className={sortMode === "price" ? "is-active" : ""} onClick={() => setSortMode("price")} type="button">
                  En ucuz
                </button>
                <button className={sortMode === "duration" ? "is-active" : ""} onClick={() => setSortMode("duration")} type="button">
                  En hizli
                </button>
                <button className={sortMode === "nonstop" ? "is-active" : ""} onClick={() => setSortMode("nonstop")} type="button">
                  Once aktarmasiz
                </button>
                <button className={sortMode === "recommended" ? "is-active" : ""} onClick={() => setSortMode("recommended")} type="button">
                  Onerilen
                </button>
              </div>

              {cartFeedback ? <div className="form-feedback success">{cartFeedback}</div> : null}
              {cartError ? <div className="form-feedback error">{cartError}</div> : null}
              {selectedCartLabel ? (
                <div className="turna-cart-banner">
                  <span>{selectedCartLabel}</span>
                  <Link href="/cart">Sepete git</Link>
                </div>
              ) : null}

              <div className="turna-results-stack">
                {visibleOffers.map((offer, index) => {
                  const baseFareOption = offer.fare_options[0];
                  const totalPrice = offer.price_amount + baseFareOption.price_delta;

                  return (
                    <article className={`turna-offer-card${index === 0 ? " is-featured" : ""}`} key={offer.id}>
                      <div className="turna-offer-main">
                        <div className="turna-offer-badges">
                          {index === 0 ? <span>En ucuz</span> : null}
                          {offer.stop_count === 0 ? <span>En hizli</span> : null}
                        </div>

                        <div className="turna-offer-airline">
                          <strong>{offer.airline_name}</strong>
                          <p>
                            {offer.provider} • {baseFareOption.label}
                          </p>
                        </div>

                        <div className="turna-offer-route">
                          <div>
                            <strong>{formatTime(offer.departure_at)}</strong>
                            <span>{offer.origin}</span>
                          </div>
                          <div className="turna-offer-center">
                            <span>{offer.duration_minutes} dk</span>
                            <div className="timeline-line" />
                            <p>{offer.stop_count === 0 ? "Direkt Ucus" : `${offer.stop_count} aktarma`}</p>
                          </div>
                          <div>
                            <strong>{formatTime(offer.arrival_at)}</strong>
                            <span>{offer.destination}</span>
                          </div>
                        </div>

                        <div className="turna-offer-foot">
                          <button className="turna-expand-chip" type="button">
                            ▼
                          </button>
                          <span>{baseFareOption.checked_baggage}</span>
                        </div>
                      </div>

                      <div className="turna-offer-side">
                        <div className="turna-offer-price">{formatPrice(totalPrice, offer.price_currency)}</div>
                        <button className="turna-select-button" onClick={() => openPackageModal(offer)} type="button">
                          Sec
                        </button>
                      </div>
                    </article>
                  );
                })}
              </div>
            </div>

            <aside className="turna-support-column" id="support">
              <div className="turna-support-card">
                <div className="turna-support-visual">◎</div>
                <strong>Canli destek hazir</strong>
                <p>Sorulariniz icin bu alan sonradan operasyon ve destek akisina baglanacak.</p>
              </div>
            </aside>
          </div>
        </div>
      </div>

      {activeOffer ? (
        <div className="turna-modal-backdrop" onClick={closePackageModal} role="presentation">
          <div className="turna-modal" onClick={(event) => event.stopPropagation()} role="dialog" aria-modal="true">
            <div className="turna-modal-head">
              <h3>Gidis paketini secin</h3>
              <button className="turna-modal-close" onClick={closePackageModal} type="button">
                ×
              </button>
            </div>

            <div className="turna-package-grid">
              {activeOffer.fare_options.map((fareOption) => {
                const isSelected = fareOption.id === selectedFareOptionId;
                return (
                  <button
                    className={`turna-package-card${isSelected ? " is-selected" : ""}`}
                    key={fareOption.id}
                    onClick={() => setSelectedFareOptionId(fareOption.id)}
                    type="button"
                  >
                    <div className="turna-package-top">
                      <div>
                        {fareOption.badge ? <span className="turna-package-badge">{fareOption.badge}</span> : null}
                        <strong>{fareOption.label}</strong>
                      </div>
                      <span className="turna-package-radio">{isSelected ? "●" : "○"}</span>
                    </div>

                    <div className="turna-package-section">
                      <span>Bagaj</span>
                      <p>{fareOption.hand_baggage}</p>
                      <p>{fareOption.checked_baggage}</p>
                    </div>

                    <div className="turna-package-section">
                      <span>Diger</span>
                      {fareOption.features.map((feature) => (
                        <p key={feature}>{feature}</p>
                      ))}
                    </div>

                    <div className="turna-package-price">
                      +{formatPrice(fareOption.price_delta, activeOffer.price_currency)}
                    </div>
                  </button>
                );
              })}
            </div>

            <div className="turna-modal-footer">
              <div className="turna-modal-summary">
                <strong>{activeOffer.airline_name}</strong>
                <span>
                  {activeFareOption?.label || activeOffer.fare_family} •{" "}
                  {formatPrice(
                    activeOffer.price_amount + (activeFareOption?.price_delta ?? 0),
                    activeOffer.price_currency,
                  )}
                </span>
              </div>
              <button className="turna-primary-cta" disabled={isAddingToCart} onClick={handleConfirmFareSelection} type="button">
                {isAddingToCart ? "Sepete ekleniyor..." : "Sec ve ilerle"}
              </button>
            </div>
          </div>
        </div>
      ) : null}
    </section>
  );
}
