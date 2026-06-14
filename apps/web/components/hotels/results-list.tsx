"use client";

import { useRouter } from "next/navigation";
import { useState } from "react";

import { apiRequest } from "@/lib/api";
import { getSession } from "@/lib/auth";
import { getOrCreateGuestSessionId } from "@/lib/guest-session";
import type { CartEnvelope } from "@/types/cart";
import type { HotelSearchEnvelope } from "@/types/hotels";

function formatPrice(value: number, currency: string) {
  return new Intl.NumberFormat("tr-TR", {
    style: "currency",
    currency,
    maximumFractionDigits: 0,
  }).format(value);
}

export function HotelResultsList({ data }: { data: HotelSearchEnvelope["data"] }) {
  const router = useRouter();
  const [feedback, setFeedback] = useState<string | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [activeOfferId, setActiveOfferId] = useState<string | null>(null);

  async function handleAddToCart(offer: HotelSearchEnvelope["data"]["offers"][number]) {
    setActiveOfferId(offer.id);
    setFeedback(null);
    setError(null);

    try {
      const session = getSession();
      const guestSessionId = session ? null : getOrCreateGuestSessionId();

      await apiRequest<CartEnvelope>("/api/cart/items/hotel", {
        method: "POST",
        token: session?.tokens.access_token,
        body: {
          item_type: "hotel",
          reference_id: offer.id,
          title: `${offer.name} ${offer.city}`,
          quantity: 1,
          unit_price: offer.total_price,
          currency: offer.currency,
          item_payload: offer,
          guest_session_id: guestSessionId,
        },
      });

      setFeedback("Secilen otel sepete eklendi.");
      router.push("/cart");
    } catch (requestError) {
      setError(requestError instanceof Error ? requestError.message : "Hotel cart request failed");
    } finally {
      setActiveOfferId(null);
    }
  }

  return (
    <section className="results-shell">
      <div className="results-route-bar">
        <div className="results-route-main">
          <strong>{data.destination_label}</strong>
          <span>{data.offers.length} otel</span>
        </div>
        <div className="results-route-meta">
          <span>{data.nights} gece</span>
          <span>Mock supplier katalogu</span>
        </div>
      </div>

      {feedback ? <div className="form-feedback success">{feedback}</div> : null}
      {error ? <div className="form-feedback error">{error}</div> : null}

      <div className="results-list">
        {data.offers.map((offer) => (
          <article className="result-card offer-card" key={offer.id}>
            <div className="offer-card-main">
              <div className="result-top-row">
                <div>
                  <strong>{offer.name}</strong>
                  <p>
                    {offer.provider} • {offer.neighborhood}
                  </p>
                </div>
                <div className="result-price">{formatPrice(offer.total_price, offer.currency)}</div>
              </div>

              <div className="result-detail-grid">
                <div>
                  <span>Gecelik</span>
                  <strong>{formatPrice(offer.nightly_price, offer.currency)}</strong>
                </div>
                <div>
                  <span>Pansiyon</span>
                  <strong>{offer.board_type}</strong>
                </div>
                <div>
                  <span>Puan</span>
                  <strong>
                    {offer.guest_score}/10 • {offer.guest_count} yorum
                  </strong>
                </div>
                <div>
                  <span>Oda</span>
                  <strong>
                    {offer.room_name} • {offer.room_size_sqm} m²
                  </strong>
                </div>
              </div>

              <div className="offer-chip-row">
                {offer.tags.map((tag) => (
                  <span key={tag}>{tag}</span>
                ))}
              </div>
            </div>

            <div className="offer-card-side">
              <span className="offer-side-note">
                {offer.star_rating} yildiz • {offer.refundable ? "Iade var" : "Iade yok"}
              </span>
              <button
                className="primary-action compact"
                onClick={() => handleAddToCart(offer)}
                type="button"
              >
                {activeOfferId === offer.id ? "Ekleniyor..." : "Sepete ekle"}
              </button>
            </div>
          </article>
        ))}
      </div>
    </section>
  );
}
