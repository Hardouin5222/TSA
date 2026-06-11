"use client";

import Link from "next/link";
import { useEffect, useState } from "react";

import { apiRequest } from "@/lib/api";
import { getSession } from "@/lib/auth";
import { getOrCreateGuestSessionId } from "@/lib/guest-session";
import type { CartEnvelope } from "@/types/cart";
import type { PaymentIntentEnvelope } from "@/types/payment";

function formatPrice(value: number, currency: string) {
  return new Intl.NumberFormat("tr-TR", {
    style: "currency",
    currency,
    maximumFractionDigits: 0,
  }).format(value);
}

export function CartPageContent() {
  const [cart, setCart] = useState<CartEnvelope["data"] | null>(null);
  const [feedback, setFeedback] = useState<string | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [paymentIntent, setPaymentIntent] = useState<PaymentIntentEnvelope["data"] | null>(null);
  const [isLoading, setIsLoading] = useState(true);
  const [isCreatingIntent, setIsCreatingIntent] = useState(false);

  useEffect(() => {
    const session = getSession();
    const guestSessionId = session ? null : getOrCreateGuestSessionId();
    const url = guestSessionId ? `/api/cart/current?guest_session_id=${guestSessionId}` : "/api/cart/current";

    apiRequest<CartEnvelope>(url, {
      token: session?.tokens.access_token,
    })
      .then((payload) => {
        setCart(payload.data);
      })
      .catch((requestError) => {
        setError(requestError instanceof Error ? requestError.message : "Cart load failed");
      })
      .finally(() => {
        setIsLoading(false);
      });
  }, []);

  async function handleCreatePaymentIntent() {
    if (!cart || !cart.cart_id || cart.items.length === 0) {
      return;
    }

    setIsCreatingIntent(true);
    setError(null);
    setFeedback(null);

    try {
      const payload = await apiRequest<PaymentIntentEnvelope>("/api/payments/intents", {
        method: "POST",
        body: {
          cart_id: cart.cart_id,
          user_id: cart.user_id,
          guest_session_id: cart.guest_session_id,
          currency: cart.currency,
          total_amount: cart.total_amount,
          items: cart.items,
        },
      });

      setPaymentIntent(payload.data);
      setFeedback(payload.message);
    } catch (requestError) {
      setError(requestError instanceof Error ? requestError.message : "Payment intent failed");
    } finally {
      setIsCreatingIntent(false);
    }
  }

  return (
    <main className="cart-page-shell">
      <div className="results-breadcrumb">
        <Link href="/">Ana sayfaya don</Link>
        <span>/</span>
        <span>Sepet</span>
      </div>

      <section className="results-shell">
        <div className="results-header-card">
          <span className="eyebrow">Checkout Foundation</span>
          <h1>Sepet ve odeme niyeti ozeti</h1>
          <p>
            Bu ekran secilen ucus tekliflerini kalici cart verisiyle gostermeye ve iyzico oncesi payment
            intent temelini kurmaya yarar.
          </p>
        </div>

        {isLoading ? (
          <div className="selection-card">
            <p>Sepet yukleniyor...</p>
          </div>
        ) : null}

        {!isLoading && cart && cart.items.length > 0 ? (
          <div className="results-layout">
            <div className="results-list">
              {cart.items.map((item) => (
                <article className="result-card active" key={item.id}>
                  <div className="result-top-row">
                    <div>
                      <strong>{item.title}</strong>
                      <p>{item.item_type} • ref {item.reference_id}</p>
                    </div>
                    <div className="result-price">{formatPrice(item.unit_price, item.currency)}</div>
                  </div>

                  <div className="result-detail-grid">
                    <div>
                      <span className="field-caption">Adet</span>
                      <strong>{item.quantity}</strong>
                    </div>
                    <div>
                      <span className="field-caption">Para birimi</span>
                      <strong>{item.currency}</strong>
                    </div>
                  </div>
                </article>
              ))}
            </div>

            <aside className="selection-card">
              <span className="eyebrow">Payment Intent</span>
              <h2>Checkout hazirligi</h2>
              <div className="selection-grid">
                <div>
                  <span>Toplam</span>
                  <strong>{formatPrice(cart.total_amount, cart.currency)}</strong>
                </div>
                <div>
                  <span>Sepet urunu</span>
                  <strong>{cart.items.length}</strong>
                </div>
              </div>
              {feedback ? <div className="form-feedback success">{feedback}</div> : null}
              {error ? <div className="form-feedback error">{error}</div> : null}
              {paymentIntent ? (
                <div className="selection-note">
                  Intent olustu: {paymentIntent.provider} • {paymentIntent.status} • ref{" "}
                  {paymentIntent.provider_reference}
                </div>
              ) : (
                <div className="selection-note">
                  Bir sonraki adimda bu intent gercek iyzico checkout linkine ve odeme callback akisina
                  baglanacak.
                </div>
              )}
              <button className="primary-action selection-action" onClick={handleCreatePaymentIntent} type="button">
                {isCreatingIntent ? "Odeme intent'i olusturuluyor..." : "Odeme adimina gec"}
              </button>
              {paymentIntent ? (
                <Link className="ghost-action selection-action" href={paymentIntent.checkout_url}>
                  Mock checkout ekranina git
                </Link>
              ) : null}
            </aside>
          </div>
        ) : null}

        {!isLoading && (!cart || cart.items.length === 0) ? (
          <div className="selection-card">
            <span className="eyebrow">Sepet bos</span>
            <h2>Henuz secili bir teklif yok.</h2>
            <p>Ucus sonuclarindan teklif secip tekrar buraya donebilirsin.</p>
            <div className="auth-cta-row">
              <Link
                className="primary-action compact"
                href="/flights?origin=IST&destination=AYT&departure_date=2026-07-18&return_date=2026-07-22&adult_count=2"
              >
                Ucus ara
              </Link>
              <Link className="ghost-action" href="/">
                Ana sayfa
              </Link>
            </div>
          </div>
        ) : null}
      </section>
    </main>
  );
}
