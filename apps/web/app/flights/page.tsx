import Link from "next/link";

import { ResultsList } from "@/components/flights/results-list";
import { serverApiRequest } from "@/lib/api";
import type { FlightSearchEnvelope } from "@/types/flights";

type FlightsPageProps = {
  searchParams: Promise<Record<string, string | string[] | undefined>>;
};

export default async function FlightsPage({ searchParams }: FlightsPageProps) {
  const params = await searchParams;

  const origin = typeof params.origin === "string" ? params.origin : "IST";
  const destination = typeof params.destination === "string" ? params.destination : "AYT";
  const departure_date =
    typeof params.departure_date === "string" ? params.departure_date : "2026-07-18";
  const return_date = typeof params.return_date === "string" ? params.return_date : "2026-07-22";
  const adult_count = Number(typeof params.adult_count === "string" ? params.adult_count : "2");

  const results = await serverApiRequest<FlightSearchEnvelope>("/api/flights/search", {
    method: "POST",
    body: {
      origin,
      destination,
      departure_date,
      return_date,
      adult_count,
      cabin_class: "economy",
    },
  });

  return (
    <main className="results-page-shell">
      <div className="results-breadcrumb">
        <Link href="/">Ana sayfaya don</Link>
        <span>/</span>
        <span>Ucus sonuclari</span>
      </div>

      <ResultsList data={results.data} />
    </main>
  );
}
