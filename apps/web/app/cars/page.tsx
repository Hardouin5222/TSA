import Link from "next/link";

import { CarResultsList } from "@/components/cars/results-list";
import { serverApiRequest } from "@/lib/api";
import type { CarSearchEnvelope } from "@/types/cars";

type CarsPageProps = {
  searchParams: Promise<Record<string, string | string[] | undefined>>;
};

export default async function CarsPage({ searchParams }: CarsPageProps) {
  const params = await searchParams;

  const pickup_location =
    typeof params.pickup_location === "string" ? params.pickup_location : "Antalya";
  const pickup_date =
    typeof params.pickup_date === "string" ? params.pickup_date : "2026-07-18";
  const dropoff_date =
    typeof params.dropoff_date === "string" ? params.dropoff_date : "2026-07-22";
  const driver_age = Number(typeof params.driver_age === "string" ? params.driver_age : "30");

  const results = await serverApiRequest<CarSearchEnvelope>("/api/cars/search", {
    method: "POST",
    body: {
      pickup_location,
      pickup_date,
      dropoff_date,
      driver_age,
    },
  });

  return (
    <main className="results-page-shell">
      <div className="results-breadcrumb">
        <Link href="/">Ana sayfaya don</Link>
        <span>/</span>
        <span>Arac kiralama sonuclari</span>
      </div>

      <CarResultsList data={results.data} />
    </main>
  );
}
