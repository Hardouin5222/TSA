"use client";

import Link from "next/link";
import { FormEvent, useEffect, useMemo, useState } from "react";

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

type TravelerForm = {
  traveler_type: string;
  first_name: string;
  last_name: string;
  birth_date: string;
};

type BillingForm = {
  invoice_type: string;
  full_name: string;
  country: string;
  city: string;
  address_line: string;
  company_name: string;
  tax_number: string;
};

export function CartPageContent() {
  const [cart, setCart] = useState<CartEnvelope["data"] | null>(null);
  const [feedback, setFeedback] = useState<string | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [paymentIntent, setPaymentIntent] = useState<PaymentIntentEnvelope["data"] | null>(null);
  const [isLoading, setIsLoading] = useState(true);
  const [isCreatingIntent, setIsCreatingIntent] = useState(false);
  const [contact, setContact] = useState({
    email: "",
    phone: "",
  });
  const [travelers, setTravelers] = useState<TravelerForm[]>([
    {
      traveler_type: "adult",
      first_name: "",
      last_name: "",
      birth_date: "",
    },
  ]);
  const [specialRequests, setSpecialRequests] = useState({
    seat_preference: "",
    meal_preference: "",
    accessibility_note: "",
  });
  const [billingDetails, setBillingDetails] = useState<BillingForm>({
    invoice_type: "individual",
    full_name: "",
    country: "Turkiye",
    city: "",
    address_line: "",
    company_name: "",
    tax_number: "",
  });

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

  useEffect(() => {
    const session = getSession();
    if (!session) {
      return;
    }

    setContact((current) => ({
      email: current.email || session.user.email,
      phone: current.phone || session.user.phone_number || "",
    }));
    setTravelers((current) => {
      if (current.length === 1 && !current[0].first_name && !current[0].last_name) {
        return [
          {
            ...current[0],
            first_name: session.user.first_name,
            last_name: session.user.last_name,
          },
        ];
      }
      return current;
    });
    setBillingDetails((current) => ({
      ...current,
      full_name:
        current.full_name || `${session.user.first_name} ${session.user.last_name}`.trim(),
    }));
  }, []);

  const isCheckoutReady = useMemo(() => {
    const hasContact = contact.email.trim().length > 3 && contact.phone.trim().length >= 6;
    const hasTravelers =
      travelers.length > 0 &&
      travelers.every(
        (traveler) =>
          traveler.first_name.trim().length > 0 &&
          traveler.last_name.trim().length > 0 &&
          traveler.birth_date.trim().length >= 8,
      );
    const hasBilling =
      billingDetails.full_name.trim().length > 0 &&
      billingDetails.country.trim().length > 0 &&
      billingDetails.city.trim().length > 0 &&
      billingDetails.address_line.trim().length > 0 &&
      (billingDetails.invoice_type !== "company" ||
        (billingDetails.company_name.trim().length > 0 && billingDetails.tax_number.trim().length > 0));
    return hasContact && hasTravelers && hasBilling;
  }, [contact, travelers, billingDetails]);

  function updateContactField(name: keyof typeof contact, value: string) {
    setContact((current) => ({ ...current, [name]: value }));
  }

  function updateTraveler(index: number, field: keyof TravelerForm, value: string) {
    setTravelers((current) =>
      current.map((traveler, travelerIndex) =>
        travelerIndex === index ? { ...traveler, [field]: value } : traveler,
      ),
    );
  }

  function updateSpecialRequestField(
    field: keyof typeof specialRequests,
    value: string,
  ) {
    setSpecialRequests((current) => ({ ...current, [field]: value }));
  }

  function updateBillingField(field: keyof BillingForm, value: string) {
    setBillingDetails((current) => ({ ...current, [field]: value }));
  }

  function addTraveler() {
    setTravelers((current) => [
      ...current,
      {
        traveler_type: "adult",
        first_name: "",
        last_name: "",
        birth_date: "",
      },
    ]);
  }

  function removeTraveler(index: number) {
    setTravelers((current) => (current.length === 1 ? current : current.filter((_, travelerIndex) => travelerIndex !== index)));
  }

  async function handleCreatePaymentIntent() {
    if (!cart || !cart.cart_id || cart.items.length === 0) {
      return;
    }

    if (!isCheckoutReady) {
      setError("Odeme adimindan once iletisim, yolcu ve fatura bilgilerini tamamla.");
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
          contact: {
            email: contact.email.trim(),
            phone: contact.phone.trim(),
          },
          travelers: travelers.map((traveler) => ({
            traveler_type: traveler.traveler_type,
            first_name: traveler.first_name.trim(),
            last_name: traveler.last_name.trim(),
            birth_date: traveler.birth_date,
          })),
          special_requests: {
            seat_preference: specialRequests.seat_preference || null,
            meal_preference: specialRequests.meal_preference || null,
            accessibility_note: specialRequests.accessibility_note || null,
          },
          billing_details: {
            invoice_type: billingDetails.invoice_type,
            full_name: billingDetails.full_name.trim(),
            country: billingDetails.country.trim(),
            city: billingDetails.city.trim(),
            address_line: billingDetails.address_line.trim(),
            company_name: billingDetails.company_name.trim() || null,
            tax_number: billingDetails.tax_number.trim() || null,
          },
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

  function handleTravelerSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    void handleCreatePaymentIntent();
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
              <article className="result-card active">
                <div className="result-top-row">
                  <div>
                    <strong>Yolcu ve iletisim bilgileri</strong>
                    <p>3 tik kuralina uygun checkout icin once iletisim ve ilk yolcu setini topluyoruz.</p>
                  </div>
                </div>

                <form className="auth-form" onSubmit={handleTravelerSubmit}>
                  <div className="auth-split-grid">
                    <label className="auth-field">
                      <span>E-posta</span>
                      <input
                        autoComplete="email"
                        onChange={(event) => updateContactField("email", event.target.value)}
                        placeholder="ornek@email.com"
                        required
                        type="email"
                        value={contact.email}
                      />
                    </label>
                    <label className="auth-field">
                      <span>Telefon</span>
                      <input
                        autoComplete="tel"
                        onChange={(event) => updateContactField("phone", event.target.value)}
                        placeholder="+90 5xx xxx xx xx"
                        required
                        value={contact.phone}
                      />
                    </label>
                  </div>

                  <div className="selection-note">
                    Bu bilgiler rezervasyon teyidi, bildirim ve ileride yolcu paneline tasinacak checkout omurgasidir.
                  </div>

                  <div className="results-list">
                    {travelers.map((traveler, index) => (
                      <article className="account-stat" key={`traveler-${index}`}>
                        <div className="result-top-row">
                          <strong>Yolcu {index + 1}</strong>
                          {travelers.length > 1 ? (
                            <button className="ghost-action" onClick={() => removeTraveler(index)} type="button">
                              Kaldir
                            </button>
                          ) : null}
                        </div>
                        <div className="auth-split-grid">
                          <label className="auth-field">
                            <span>Ad</span>
                            <input
                              onChange={(event) => updateTraveler(index, "first_name", event.target.value)}
                              placeholder="Ad"
                              required
                              value={traveler.first_name}
                            />
                          </label>
                          <label className="auth-field">
                            <span>Soyad</span>
                            <input
                              onChange={(event) => updateTraveler(index, "last_name", event.target.value)}
                              placeholder="Soyad"
                              required
                              value={traveler.last_name}
                            />
                          </label>
                        </div>
                        <div className="auth-split-grid">
                          <label className="auth-field">
                            <span>Dogum tarihi</span>
                            <input
                              onChange={(event) => updateTraveler(index, "birth_date", event.target.value)}
                              required
                              type="date"
                              value={traveler.birth_date}
                            />
                          </label>
                          <label className="auth-field">
                            <span>Yolcu tipi</span>
                            <select
                              onChange={(event) => updateTraveler(index, "traveler_type", event.target.value)}
                              value={traveler.traveler_type}
                            >
                              <option value="adult">Yetiskin</option>
                              <option value="child">Cocuk</option>
                              <option value="infant">Bebek</option>
                            </select>
                          </label>
                        </div>
                      </article>
                    ))}
                  </div>

                  <div className="auth-cta-row">
                    <button className="ghost-action" onClick={addTraveler} type="button">
                      Yolcu ekle
                    </button>
                  </div>

                  <article className="account-stat">
                    <div className="result-top-row">
                      <strong>Ozel istekler</strong>
                      <span className="field-caption">Opsiyonel ama donusum icin faydali</span>
                    </div>
                    <div className="auth-split-grid">
                      <label className="auth-field">
                        <span>Koltuk tercihi</span>
                        <select
                          onChange={(event) => updateSpecialRequestField("seat_preference", event.target.value)}
                          value={specialRequests.seat_preference}
                        >
                          <option value="">Secilmedi</option>
                          <option value="window">Cam kenari</option>
                          <option value="aisle">Koridor</option>
                          <option value="front">On siralar</option>
                        </select>
                      </label>
                      <label className="auth-field">
                        <span>Yemek tercihi</span>
                        <select
                          onChange={(event) => updateSpecialRequestField("meal_preference", event.target.value)}
                          value={specialRequests.meal_preference}
                        >
                          <option value="">Secilmedi</option>
                          <option value="standard">Standart</option>
                          <option value="vegetarian">Vejetaryen</option>
                          <option value="child">Cocuk menusu</option>
                        </select>
                      </label>
                    </div>
                    <label className="auth-field">
                      <span>Erisilebilirlik veya destek notu</span>
                      <textarea
                        onChange={(event) => updateSpecialRequestField("accessibility_note", event.target.value)}
                        placeholder="Tekerlekli sandalye, yardim ihtiyaci, oncelikli destek..."
                        rows={3}
                        value={specialRequests.accessibility_note}
                      />
                    </label>
                  </article>

                  <article className="account-stat">
                    <div className="result-top-row">
                      <strong>Fatura bilgileri</strong>
                      <span className="field-caption">Odeme sonrasi belge akisi icin hazirlik</span>
                    </div>
                    <div className="auth-split-grid">
                      <label className="auth-field">
                        <span>Fatura tipi</span>
                        <select
                          onChange={(event) => updateBillingField("invoice_type", event.target.value)}
                          value={billingDetails.invoice_type}
                        >
                          <option value="individual">Bireysel</option>
                          <option value="company">Sirket</option>
                        </select>
                      </label>
                      <label className="auth-field">
                        <span>Fatura unvani</span>
                        <input
                          onChange={(event) => updateBillingField("full_name", event.target.value)}
                          placeholder="Ad Soyad veya sirket yetkilisi"
                          required
                          value={billingDetails.full_name}
                        />
                      </label>
                    </div>
                    <div className="auth-split-grid">
                      <label className="auth-field">
                        <span>Ulke</span>
                        <input
                          onChange={(event) => updateBillingField("country", event.target.value)}
                          required
                          value={billingDetails.country}
                        />
                      </label>
                      <label className="auth-field">
                        <span>Sehir</span>
                        <input
                          onChange={(event) => updateBillingField("city", event.target.value)}
                          placeholder="Istanbul"
                          required
                          value={billingDetails.city}
                        />
                      </label>
                    </div>
                    <label className="auth-field">
                      <span>Adres</span>
                      <textarea
                        onChange={(event) => updateBillingField("address_line", event.target.value)}
                        placeholder="Mahalle, sokak, bina, ilce"
                        required
                        rows={3}
                        value={billingDetails.address_line}
                      />
                    </label>
                    {billingDetails.invoice_type === "company" ? (
                      <div className="auth-split-grid">
                        <label className="auth-field">
                          <span>Sirket unvani</span>
                          <input
                            onChange={(event) => updateBillingField("company_name", event.target.value)}
                            placeholder="ABC Turizm A.S."
                            required
                            value={billingDetails.company_name}
                          />
                        </label>
                        <label className="auth-field">
                          <span>Vergi no</span>
                          <input
                            onChange={(event) => updateBillingField("tax_number", event.target.value)}
                            placeholder="1234567890"
                            required
                            value={billingDetails.tax_number}
                          />
                        </label>
                      </div>
                    ) : null}
                  </article>
                </form>
              </article>

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
                <div>
                  <span>Yolcu</span>
                  <strong>{travelers.length}</strong>
                </div>
                <div>
                  <span>Iletisim</span>
                  <strong>{contact.email ? "hazir" : "eksik"}</strong>
                </div>
                <div>
                  <span>Fatura</span>
                  <strong>{billingDetails.invoice_type === "company" ? "sirket" : "bireysel"}</strong>
                </div>
                <div>
                  <span>Ozel istek</span>
                  <strong>
                    {specialRequests.seat_preference || specialRequests.meal_preference || specialRequests.accessibility_note
                      ? "var"
                      : "yok"}
                  </strong>
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
                  Iletisim ve yolcu bilgileri payment intent icine kaydedilir. Sonraki adimda bu intent gercek
                  iyzico checkout linkine ve odeme callback akisina baglanacak.
                </div>
              )}
              <button
                className="primary-action selection-action"
                disabled={isCreatingIntent || !isCheckoutReady}
                onClick={handleCreatePaymentIntent}
                type="button"
              >
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
