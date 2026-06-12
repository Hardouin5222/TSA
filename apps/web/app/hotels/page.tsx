import Link from "next/link";

import { HotelResultsList } from "@/components/hotels/results-list";
import { serverApiRequest } from "@/lib/api";
import type { HotelSearchEnvelope } from "@/types/hotels";

type HotelsPageProps = {
  searchParams: Promise<Record<string, string | string[] | undefined>>;
};

export default async function HotelsPage({ searchParams }: HotelsPageProps) {
  const params = await searchParams;

  const city = typeof params.city === "string" ? params.city : "Antalya";
  const check_in = typeof params.check_in === "string" ? params.check_in : "2026-07-18";
  const check_out = typeof params.check_out === "string" ? params.check_out : "2026-07-22";
  const adult_count = Number(typeof params.adult_count === "string" ? params.adult_count : "2");
  const room_count = Number(typeof params.room_count === "string" ? params.room_count : "1");

  const results = await serverApiRequest<HotelSearchEnvelope>("/api/hotels/search", {
    method: "POST",
    body: {
      city,
      check_in,
      check_out,
      adult_count,
      room_count,
    },
  });

  return (
    <main className="results-page-shell">
      <div className="results-breadcrumb">
        <Link href="/">Ana sayfaya don</Link>
        <span>/</span>
        <span>Otel sonuclari</span>
      </div>

      <HotelResultsList data={results.data} />
    </main>
  );
}
