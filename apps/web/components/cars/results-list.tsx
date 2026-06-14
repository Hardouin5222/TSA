"use client";

import { useRouter } from "next/navigation";
import { useState } from "react";

import { apiRequest } from "@/lib/api";
import { getSession } from "@/lib/auth";
import { getOrCreateGuestSessionId } from "@/lib/guest-session";
import type { CartEnvelope } from "@/types/cart";
import type { CarSearchEnvelope } from "@/types/cars";

function formatPrice(value: number, currency: string) {
  return new Intl.NumberFormat("tr-TR", {
    style: "currency",
    currency,
    maximumFractionDigits: 0,
  }).format(value);
}

export function CarResultsList({ data }: { data: CarSearchEnvelope["data"] }) {
  const router = useRouter();
  const [feedback, setFeedback] = useState<string | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [activeOfferId, setActiveOfferId] = useState<string | null>(null);

  async function handleAddToCart(offer: CarSearchEnvelope["data"]["offers"][number]) {
    setActiveOfferId(offer.id);
    setFeedback(null);
    setError(null);

    try {
      const session = getSession();
      const guestSessionId = session ? null : getOrCreateGuestSessionId();

      await apiRequest<CartEnvelope>("/api/cart/items/car", {
        method: "POST",
        token: session?.tokens.access_token,
        body: {
          item_type: "car",
          reference_id: offer.id,
          title: `${offer.vendor_name} ${offer.vehicle_name}`,
          quantity: 1,
          unit_price: offer.total_price,
          currency: offer.currency,
          item_payload: offer,
          guest_session_id: guestSessionId,
        },
      });

      setFeedback("Secilen arac sepete eklendi.");
      router.push("/cart");
    } catch (requestError) {
      setError(requestError instanceof Error ? requestError.message : "Car cart request failed");
    } finally {
      setActiveOfferId(null);
    }
  }

  return (
    <section className="results-shell">
      <div className="results-route-bar">
        <div className="results-route-main">
          <strong>{data.route_label}</strong>
          <span>{data.offers.length} arac</span>
        </div>
        <div className="results-route-meta">
          <span>{data.rental_days} gun</span>
          <span>Supplier-ready mock</span>
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
                  <strong>{offer.vendor_name}</strong>
                  <p>
                    {offer.vehicle_name} • {offer.category}
                  </p>
                </div>
                <div className="result-price">{formatPrice(offer.total_price, offer.currency)}</div>
              </div>

              <div className="result-detail-grid">
                <div>
                  <span>Vites</span>
                  <strong>{offer.transmission}</strong>
                </div>
                <div>
                  <span>Yakit</span>
                  <strong>{offer.fuel_policy}</strong>
                </div>
                <div>
                  <span>Kapasite</span>
                  <strong>
                    {offer.seats} kisi • {offer.bags} bagaj
                  </strong>
                </div>
                <div>
                  <span>Ozellik</span>
                  <strong>{offer.air_conditioning ? "Klima var" : "Klima yok"}</strong>
                </div>
              </div>

              <div className="offer-chip-row">
                {offer.tags.map((tag) => (
                  <span key={tag}>{tag}</span>
                ))}
              </div>
            </div>

            <div className="offer-card-side">
              <span className="offer-side-note">{formatPrice(offer.daily_price, offer.currency)} / gun</span>
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
