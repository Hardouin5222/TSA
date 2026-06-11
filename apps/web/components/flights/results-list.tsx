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

export function ResultsList({ data }: { data: FlightSearchEnvelope["data"] }) {
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

      <div className="results-list">
        {data.offers.map((offer) => (
          <article className="result-card" key={offer.id}>
            <div className="result-top-row">
              <div>
                <strong>
                  {offer.airline_name} <span>{offer.airline_code}</span>
                </strong>
                <p>
                  {offer.provider} uzerinden sunuluyor • {offer.cabin_class}
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

            <div className="result-bottom-row">
              <div className="result-tags">
                {offer.tags.map((tag) => (
                  <span key={tag}>{tag}</span>
                ))}
                <span>{offer.baggage_summary}</span>
              </div>
              <button className="primary-action compact" type="button">
                Devam et
              </button>
            </div>
          </article>
        ))}
      </div>
    </section>
  );
}
