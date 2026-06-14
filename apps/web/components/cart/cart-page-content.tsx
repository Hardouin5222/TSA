"use client";

import Link from "next/link";
import { FormEvent, useEffect, useMemo, useState } from "react";

import { apiRequest } from "@/lib/api";
import { getSession } from "@/lib/auth";
import { getFareOptionServiceFlags, getFlightCapabilitySummary } from "@/lib/flight-capabilities";
import { getOrCreateGuestSessionId } from "@/lib/guest-session";
import { getProductSummary } from "@/lib/product-summary";
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

type FareOptionSnapshot = {
  id: string;
  label: string;
  seat_selection: boolean;
  meal_included: boolean;
  service_flags?: {
    seat_selection?: boolean;
    meal_selection?: boolean;
    refundable?: boolean;
    exchangeable?: boolean;
  };
};

function readString(payload: Record<string, unknown>, key: string, fallback = "") {
  const value = payload[key];
  return typeof value === "string" ? value : fallback;
}

function extractSelectedFareOption(item: CartEnvelope["data"]["items"][number] | null): FareOptionSnapshot | null {
  if (!item) {
    return null;
  }

  const payload = item.item_payload as Record<string, unknown>;
  const selectedId = typeof payload.selected_fare_option_id === "string" ? payload.selected_fare_option_id : null;
  const rawFareOptions = Array.isArray(payload.fare_options) ? payload.fare_options : [];

  const matchingOption =
    rawFareOptions.find((entry) => {
      if (!entry || typeof entry !== "object") {
        return false;
      }

      return typeof (entry as { id?: unknown }).id === "string" && (entry as { id: string }).id === selectedId;
    }) ?? rawFareOptions[0];

  if (!matchingOption || typeof matchingOption !== "object") {
    return null;
  }

  const option = matchingOption as Record<string, unknown>;
  return {
    id: typeof option.id === "string" ? option.id : "",
    label: typeof option.label === "string" ? option.label : "",
    seat_selection: Boolean(option.seat_selection),
    meal_included: Boolean(option.meal_included),
    service_flags:
      typeof option.service_flags === "object" && option.service_flags !== null
        ? (option.service_flags as FareOptionSnapshot["service_flags"])
        : undefined,
  };
}

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
    city: "Istanbul",
    address_line: "",
    company_name: "",
    tax_number: "",
  });
  const [insuranceSelected, setInsuranceSelected] = useState(false);

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
      full_name: current.full_name || `${session.user.first_name} ${session.user.last_name}`.trim(),
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

  const primaryItem = cart?.items[0] ?? null;
  const productSummary = getProductSummary(primaryItem);
  const selectedFareOption = extractSelectedFareOption(primaryItem);
  const flightCapabilities =
    primaryItem && primaryItem.item_type === "flight" ? getFlightCapabilitySummary(primaryItem.item_payload) : null;
  const fareOptionFlags = getFareOptionServiceFlags(selectedFareOption);
  const showSeatPreference = Boolean(flightCapabilities?.seatSelectionSupported && fareOptionFlags.seatSelection);
  const showMealPreference = Boolean(flightCapabilities?.mealSelectionSupported && fareOptionFlags.mealSelection);
  const showTravelerNote = Boolean(flightCapabilities?.travelerNoteSupported);
  const showSpecialRequestsSection = showSeatPreference || showMealPreference || showTravelerNote;

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

  function updateSpecialRequestField(field: keyof typeof specialRequests, value: string) {
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
    setTravelers((current) =>
      current.length === 1 ? current : current.filter((_, travelerIndex) => travelerIndex !== index),
    );
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
          special_requests: showSpecialRequestsSection
            ? {
                seat_preference: showSeatPreference ? specialRequests.seat_preference || null : null,
                meal_preference: showMealPreference ? specialRequests.meal_preference || null : null,
                accessibility_note: specialRequests.accessibility_note || null,
              }
            : null,
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

      <section className="turna-process-shell">
        <div className="turna-process-bar">
          <div className="turna-process-brand">
            <strong>Travel Super App</strong>
            <span>Checkout flow</span>
          </div>

          <div className="turna-process-steps" aria-label="Satin alma adimlari">
            <div className="turna-process-step is-complete">
              <span>1</span>
              <strong>Ucus secimi</strong>
            </div>
            <div className="turna-process-step is-active">
              <span>2</span>
              <strong>Yolcu bilgileri</strong>
            </div>
            <div className={`turna-process-step${paymentIntent ? " is-active" : ""}`}>
              <span>3</span>
              <strong>Odeme</strong>
            </div>
          </div>
        </div>

        {isLoading ? (
          <div className="turna-process-card">
            <p>Sepet yukleniyor...</p>
          </div>
        ) : null}

        {!isLoading && cart && cart.items.length > 0 ? (
          <div className="turna-checkout-grid">
            <div className="turna-checkout-main">
              <section className="turna-process-card turna-package-summary-card">
                <div className="turna-card-head">
                  <div>
                    <span className="turna-card-label">Gidis paketi</span>
                    <h1>Yolcu bilgileri ve odeme hazirligi</h1>
                  </div>
                  <Link className="turna-link-action" href="/flights?origin=IST&destination=AYT&departure_date=2026-07-18&return_date=2026-07-22&adult_count=2">
                    Secimi degistir
                  </Link>
                </div>

                {productSummary ? (
                  <div className="turna-package-summary-row">
                    <div className="turna-package-chip">
                      <span>Secim</span>
                      <strong>{productSummary.title}</strong>
                    </div>
                    <div className="turna-package-chip">
                      <span>Alt baslik</span>
                      <strong>{productSummary.subtitle || "-"}</strong>
                    </div>
                    <div className="turna-package-chip">
                      <span>Urun tipi</span>
                      <strong>{primaryItem?.item_type || "-"}</strong>
                    </div>
                  </div>
                ) : null}
              </section>

              <form className="turna-checkout-form" onSubmit={handleTravelerSubmit}>
                <section className="turna-process-card">
                  <div className="turna-section-title">
                    <strong>Yolcu bilgileri</strong>
                    <span>Temel alanlari once gosteriyoruz, kalan tercihler altta.</span>
                  </div>

                  <div className="turna-passenger-stack">
                    {travelers.map((traveler, index) => (
                      <article className="turna-passenger-card" key={`traveler-${index}`}>
                        <div className="turna-passenger-head">
                          <div>
                            <strong>Yetiskin</strong>
                            <span>Yolcu {index + 1}</span>
                          </div>
                          {travelers.length > 1 ? (
                            <button className="turna-text-button" onClick={() => removeTraveler(index)} type="button">
                              Kaldir
                            </button>
                          ) : null}
                        </div>

                        <div className="turna-field-grid">
                          <label className="turna-field">
                            <span>Ad</span>
                            <input
                              onChange={(event) => updateTraveler(index, "first_name", event.target.value)}
                              placeholder="Ad"
                              required
                              value={traveler.first_name}
                            />
                          </label>

                          <label className="turna-field">
                            <span>Soyad</span>
                            <input
                              onChange={(event) => updateTraveler(index, "last_name", event.target.value)}
                              placeholder="Soyad"
                              required
                              value={traveler.last_name}
                            />
                          </label>

                          <label className="turna-field">
                            <span>Dogum tarihi</span>
                            <input
                              onChange={(event) => updateTraveler(index, "birth_date", event.target.value)}
                              required
                              type="date"
                              value={traveler.birth_date}
                            />
                          </label>

                          <label className="turna-field">
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

                  <button className="turna-outline-button" onClick={addTraveler} type="button">
                    Yolcu ekle
                  </button>
                </section>

                <section className="turna-process-card">
                  <div className="turna-section-title">
                    <strong>{primaryItem?.item_type === "flight" ? "Bagaj" : "Secili urun"}</strong>
                    <span>
                      {primaryItem?.item_type === "flight"
                        ? "Secilen paket kapsaminda dahil olan alanlar."
                        : "Secilen urunun ozet bilgilerini burada tutuyoruz."}
                    </span>
                  </div>

                  <div className="turna-baggage-grid">
                    <div className="turna-baggage-card">
                      <strong>{productSummary?.meta[0]?.label || "Detay 1"}</strong>
                      <span>{productSummary?.meta[0]?.value || "-"}</span>
                      <em>Dahil</em>
                    </div>
                    <div className="turna-baggage-card">
                      <strong>{productSummary?.meta[1]?.label || "Detay 2"}</strong>
                      <span>{productSummary?.meta[1]?.value || "-"}</span>
                      <em>Dahil</em>
                    </div>
                  </div>
                </section>

                <section className="turna-process-card">
                  <div className="turna-section-title">
                    <strong>Iletisim bilgileri</strong>
                    <span>Bilet ve rezervasyon bildirimi bu bilgilere gider.</span>
                  </div>

                  <div className="turna-field-grid">
                    <label className="turna-field">
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

                    <label className="turna-field">
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

                  <label className="turna-checkline">
                    <input type="checkbox" />
                    <span>Firsatlar ve kampanyalar hakkinda e-posta veya SMS almak istiyorum.</span>
                  </label>
                </section>

                <section className="turna-process-card turna-insurance-card">
                  <div className="turna-insurance-head">
                    <div>
                      <strong>Biletini korumaya al</strong>
                      <span>Opsiyonel koruma urunu. Simdilik mock seviye hazirlik olarak duruyor.</span>
                    </div>

                    <label className="turna-insurance-toggle">
                      <span>Onerilen</span>
                      <input
                        checked={insuranceSelected}
                        onChange={() => setInsuranceSelected((current) => !current)}
                        type="checkbox"
                      />
                    </label>
                  </div>

                  <div className="turna-insurance-body">
                    <p>Bilet tutarinin buyuk kismini iade eden koruma urunlerini bu bolgeye baglayacagiz.</p>
                    <strong>1 kisi icin hazir fiyat alani</strong>
                  </div>
                </section>

                {showSpecialRequestsSection ? (
                  <details className="turna-process-card turna-collapse-card">
                    <summary>Paketle gelen ek tercihler</summary>
                    <div className="turna-collapse-body">
                      <div className="turna-field-grid">
                        {showSeatPreference ? (
                          <label className="turna-field">
                            <span>Koltuk tercihi</span>
                            <select
                              onChange={(event) => updateSpecialRequestField("seat_preference", event.target.value)}
                              value={specialRequests.seat_preference}
                            >
                              <option value="">Secilmedi</option>
                              <option value="Koridor">Koridor</option>
                              <option value="Cam kenari">Cam kenari</option>
                              <option value="On siralar">On siralar</option>
                            </select>
                          </label>
                        ) : null}

                        {showMealPreference ? (
                          <label className="turna-field">
                            <span>Yemek tercihi</span>
                            <select
                              onChange={(event) => updateSpecialRequestField("meal_preference", event.target.value)}
                              value={specialRequests.meal_preference}
                            >
                              <option value="">Secilmedi</option>
                              <option value="Standart">Standart</option>
                              <option value="Vejetaryen">Vejetaryen</option>
                              <option value="Cocuk menusu">Cocuk menusu</option>
                            </select>
                          </label>
                        ) : null}
                      </div>

                      {showTravelerNote ? (
                        <label className="turna-field">
                          <span>Ek destek notu</span>
                          <textarea
                            onChange={(event) => updateSpecialRequestField("accessibility_note", event.target.value)}
                            placeholder="Varsa operasyon ekibine iletilecek ek notunuzu yazin."
                            rows={3}
                            value={specialRequests.accessibility_note}
                          />
                        </label>
                      ) : null}
                    </div>
                  </details>
                ) : null}

                <details className="turna-process-card turna-collapse-card">
                  <summary>Fatura bilgileri</summary>
                  <div className="turna-collapse-body">
                    <div className="turna-field-grid">
                      <label className="turna-field">
                        <span>Fatura tipi</span>
                        <select
                          onChange={(event) => updateBillingField("invoice_type", event.target.value)}
                          value={billingDetails.invoice_type}
                        >
                          <option value="individual">Bireysel</option>
                          <option value="company">Sirket</option>
                        </select>
                      </label>

                      <label className="turna-field">
                        <span>Fatura unvani</span>
                        <input
                          onChange={(event) => updateBillingField("full_name", event.target.value)}
                          placeholder="Ad Soyad veya sirket yetkilisi"
                          required
                          value={billingDetails.full_name}
                        />
                      </label>

                      <label className="turna-field">
                        <span>Ulke</span>
                        <input
                          onChange={(event) => updateBillingField("country", event.target.value)}
                          required
                          value={billingDetails.country}
                        />
                      </label>

                      <label className="turna-field">
                        <span>Sehir</span>
                        <input
                          onChange={(event) => updateBillingField("city", event.target.value)}
                          placeholder="Istanbul"
                          required
                          value={billingDetails.city}
                        />
                      </label>
                    </div>

                    <label className="turna-field">
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
                      <div className="turna-field-grid">
                        <label className="turna-field">
                          <span>Sirket unvani</span>
                          <input
                            onChange={(event) => updateBillingField("company_name", event.target.value)}
                            placeholder="ABC Turizm A.S."
                            required
                            value={billingDetails.company_name}
                          />
                        </label>

                        <label className="turna-field">
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
                  </div>
                </details>
              </form>
            </div>

            <aside className="turna-summary-panel">
              <section className="turna-summary-card">
                <div className="turna-summary-head">
                  <strong>Gidis</strong>
                </div>

                {productSummary?.timeline ? (
                  <div className="turna-itinerary-block">
                    <div className="turna-itinerary-row">
                      <div>
                        <strong>{productSummary.timeline.leftTime}</strong>
                        <span>{productSummary.timeline.leftLabel}</span>
                      </div>
                      <div className="turna-itinerary-middle">
                        <span>{productSummary.timeline.middleLabel}</span>
                        <p>{productSummary.timeline.middleSubLabel}</p>
                      </div>
                      <div>
                        <strong>{productSummary.timeline.rightTime}</strong>
                        <span>{productSummary.timeline.rightLabel}</span>
                      </div>
                    </div>

                    <div className="turna-summary-meta">
                      <span>{productSummary.title}</span>
                      <span>{productSummary.subtitle}</span>
                    </div>
                  </div>
                ) : productSummary ? (
                  <div className="turna-inline-grid">
                    <div>
                      <span>Urun</span>
                      <strong>{productSummary.title}</strong>
                    </div>
                    <div>
                      <span>Detay</span>
                      <strong>{productSummary.subtitle || "-"}</strong>
                    </div>
                  </div>
                ) : null}
              </section>

              <section className="turna-summary-card">
                <div className="turna-summary-head">
                  <strong>Odeme detayi</strong>
                </div>

                <div className="turna-summary-list">
                  <div>
                    <span>Urunler ({travelers.length} yolcu)</span>
                    <strong>{formatPrice(cart.total_amount, cart.currency)}</strong>
                  </div>
                  <div>
                    <span>{productSummary?.meta[0]?.label || "Detay"}</span>
                    <strong>{productSummary?.meta[0]?.value || "-"}</strong>
                  </div>
                  <div>
                    <span>Secili urun</span>
                    <strong>{productSummary?.title || "-"}</strong>
                  </div>
                  <div>
                    <span>Toplam</span>
                    <strong>{formatPrice(cart.total_amount, cart.currency)}</strong>
                  </div>
                </div>

                <div className="turna-inline-grid">
                  <div>
                    <span>Iletisim</span>
                    <strong>{contact.email && contact.phone ? "Hazir" : "Eksik"}</strong>
                  </div>
                  <div>
                    <span>Fatura</span>
                    <strong>{billingDetails.invoice_type === "company" ? "Sirket" : "Bireysel"}</strong>
                  </div>
                </div>

                {feedback ? <div className="form-feedback success">{feedback}</div> : null}
                {error ? <div className="form-feedback error">{error}</div> : null}

                {paymentIntent ? (
                  <div className="turna-inline-note">
                    Intent olustu: {paymentIntent.provider} / {paymentIntent.status}
                    <br />
                    Ref: {paymentIntent.provider_reference}
                  </div>
                ) : (
                  <div className="turna-inline-note">
                    Bu adimda yolcu, iletisim ve fatura verisini baglayip sonra odeme ekranina geciyoruz.
                  </div>
                )}

                <button
                  className="turna-primary-button"
                  disabled={isCreatingIntent || !isCheckoutReady}
                  onClick={handleCreatePaymentIntent}
                  type="button"
                >
                  {isCreatingIntent ? "Hazirlaniyor..." : "Odeme adimina gec"}
                </button>

                {paymentIntent ? (
                  <Link className="turna-secondary-button" href={paymentIntent.checkout_url}>
                    Mock checkout ekranina git
                  </Link>
                ) : null}
              </section>
            </aside>
          </div>
        ) : null}

        {!isLoading && (!cart || cart.items.length === 0) ? (
          <div className="turna-process-card">
            <span className="turna-card-label">Sepet bos</span>
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
