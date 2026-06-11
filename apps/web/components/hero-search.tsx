"use client";

import { useRouter } from "next/navigation";
import { FormEvent, useState } from "react";

export function HeroSearch() {
  const router = useRouter();
  const [form, setForm] = useState({
    origin: "IST",
    destination: "AYT",
    departure_date: "2026-07-18",
    return_date: "2026-07-22",
    adult_count: "2",
  });

  function updateField(name: keyof typeof form, value: string) {
    setForm((current) => ({ ...current, [name]: value }));
  }

  function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();

    const params = new URLSearchParams({
      origin: form.origin,
      destination: form.destination,
      departure_date: form.departure_date,
      return_date: form.return_date,
      adult_count: form.adult_count,
    });

    router.push(`/flights?${params.toString()}`);
  }

  return (
    <form className="hero-search" id="search" aria-label="Main search" onSubmit={handleSubmit}>
      <div className="journey-toggle">
        <button className="is-selected" type="button">
          Gidis - donus
        </button>
        <button type="button">Tek yon</button>
        <button type="button">Coklu ucus</button>
      </div>

      <div className="search-grid">
        <label className="search-field span-3">
          <span className="field-caption">Nereden</span>
          <input
            onChange={(event) => updateField("origin", event.target.value.toUpperCase())}
            value={form.origin}
          />
          <small>IATA kodu veya sehir</small>
        </label>
        <label className="search-field span-3">
          <span className="field-caption">Nereye</span>
          <input
            onChange={(event) => updateField("destination", event.target.value.toUpperCase())}
            value={form.destination}
          />
          <small>Destinasyon secimi</small>
        </label>
        <label className="search-field span-2">
          <span className="field-caption">Gidis</span>
          <input onChange={(event) => updateField("departure_date", event.target.value)} type="date" value={form.departure_date} />
          <small>Satin alma niyeti yuksek tarih</small>
        </label>
        <label className="search-field span-2">
          <span className="field-caption">Donus</span>
          <input onChange={(event) => updateField("return_date", event.target.value)} type="date" value={form.return_date} />
          <small>Paket eslestirmesi icin onemli</small>
        </label>
        <label className="search-field span-2">
          <span className="field-caption">Yolcu</span>
          <select onChange={(event) => updateField("adult_count", event.target.value)} value={form.adult_count}>
            <option value="1">1 yetiskin</option>
            <option value="2">2 yetiskin</option>
            <option value="3">3 yetiskin</option>
            <option value="4">4 yetiskin</option>
          </select>
          <small>Oda ve paket mantigi icin kullanilir</small>
        </label>
        <div className="search-submit">
          <button className="primary-action" type="submit">
            Fiyatlari goster
          </button>
          <span className="supporting-copy">3 tik akisi: ara, sec, satin al.</span>
        </div>
      </div>
    </form>
  );
}
