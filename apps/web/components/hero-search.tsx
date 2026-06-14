"use client";

import { useRouter } from "next/navigation";
import { FormEvent, useState } from "react";

export function HeroSearch() {
  const router = useRouter();
  const [mode, setMode] = useState<"flight" | "hotel" | "car">("flight");
  const [flightForm, setFlightForm] = useState({
    origin: "IST",
    destination: "AYT",
    departure_date: "2026-07-18",
    return_date: "2026-07-22",
    adult_count: "2",
  });
  const [hotelForm, setHotelForm] = useState({
    city: "Antalya",
    check_in: "2026-07-18",
    check_out: "2026-07-22",
    adult_count: "2",
    room_count: "1",
  });
  const [carForm, setCarForm] = useState({
    pickup_location: "Antalya",
    pickup_date: "2026-07-18",
    dropoff_date: "2026-07-22",
    driver_age: "30",
  });

  function updateFlightField(name: keyof typeof flightForm, value: string) {
    setFlightForm((current) => ({ ...current, [name]: value }));
  }

  function updateHotelField(name: keyof typeof hotelForm, value: string) {
    setHotelForm((current) => ({ ...current, [name]: value }));
  }

  function updateCarField(name: keyof typeof carForm, value: string) {
    setCarForm((current) => ({ ...current, [name]: value }));
  }

  function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();

    if (mode === "flight") {
      const params = new URLSearchParams({
        origin: flightForm.origin,
        destination: flightForm.destination,
        departure_date: flightForm.departure_date,
        return_date: flightForm.return_date,
        adult_count: flightForm.adult_count,
      });
      router.push(`/flights?${params.toString()}`);
      return;
    }

    if (mode === "hotel") {
      const params = new URLSearchParams({
        city: hotelForm.city,
        check_in: hotelForm.check_in,
        check_out: hotelForm.check_out,
        adult_count: hotelForm.adult_count,
        room_count: hotelForm.room_count,
      });
      router.push(`/hotels?${params.toString()}`);
      return;
    }

    const params = new URLSearchParams({
      pickup_location: carForm.pickup_location,
      pickup_date: carForm.pickup_date,
      dropoff_date: carForm.dropoff_date,
      driver_age: carForm.driver_age,
    });
    router.push(`/cars?${params.toString()}`);
  }

  return (
    <form className="hero-search" id="search" aria-label="Main search" onSubmit={handleSubmit}>
      <div className="journey-toggle">
        <button className={mode === "flight" ? "is-selected" : ""} onClick={() => setMode("flight")} type="button">
          Ucak
        </button>
        <button className={mode === "hotel" ? "is-selected" : ""} onClick={() => setMode("hotel")} type="button">
          Otel
        </button>
        <button className={mode === "car" ? "is-selected" : ""} onClick={() => setMode("car")} type="button">
          Arac
        </button>
      </div>

      {mode === "flight" ? (
        <div className="search-grid">
          <label className="search-field span-3">
            <span className="field-caption">Nereden</span>
            <input
              onChange={(event) => updateFlightField("origin", event.target.value.toUpperCase())}
              value={flightForm.origin}
            />
            <small>IATA kodu veya sehir</small>
          </label>
          <label className="search-field span-3">
            <span className="field-caption">Nereye</span>
            <input
              onChange={(event) => updateFlightField("destination", event.target.value.toUpperCase())}
              value={flightForm.destination}
            />
            <small>Destinasyon secimi</small>
          </label>
          <label className="search-field span-2">
            <span className="field-caption">Gidis</span>
            <input
              onChange={(event) => updateFlightField("departure_date", event.target.value)}
              type="date"
              value={flightForm.departure_date}
            />
            <small>Satin alma niyeti yuksek tarih</small>
          </label>
          <label className="search-field span-2">
            <span className="field-caption">Donus</span>
            <input
              onChange={(event) => updateFlightField("return_date", event.target.value)}
              type="date"
              value={flightForm.return_date}
            />
            <small>Paket eslestirmesi icin onemli</small>
          </label>
          <label className="search-field span-2">
            <span className="field-caption">Yolcu</span>
            <select onChange={(event) => updateFlightField("adult_count", event.target.value)} value={flightForm.adult_count}>
              <option value="1">1 yetiskin</option>
              <option value="2">2 yetiskin</option>
              <option value="3">3 yetiskin</option>
              <option value="4">4 yetiskin</option>
            </select>
            <small>3 tik akisi: ara, sec, satin al.</small>
          </label>
          <div className="search-submit">
            <button className="primary-action" type="submit">
              Ucuslari goster
            </button>
            <span className="supporting-copy">Turna benzeri sade arama akisi.</span>
          </div>
        </div>
      ) : null}

      {mode === "hotel" ? (
        <div className="search-grid">
          <label className="search-field span-4">
            <span className="field-caption">Sehir</span>
            <input onChange={(event) => updateHotelField("city", event.target.value)} value={hotelForm.city} />
            <small>Sehir, bolge veya destinasyon</small>
          </label>
          <label className="search-field span-2">
            <span className="field-caption">Giris</span>
            <input onChange={(event) => updateHotelField("check_in", event.target.value)} type="date" value={hotelForm.check_in} />
            <small>Check-in</small>
          </label>
          <label className="search-field span-2">
            <span className="field-caption">Cikis</span>
            <input onChange={(event) => updateHotelField("check_out", event.target.value)} type="date" value={hotelForm.check_out} />
            <small>Check-out</small>
          </label>
          <label className="search-field span-2">
            <span className="field-caption">Misafir</span>
            <select onChange={(event) => updateHotelField("adult_count", event.target.value)} value={hotelForm.adult_count}>
              <option value="1">1 misafir</option>
              <option value="2">2 misafir</option>
              <option value="3">3 misafir</option>
              <option value="4">4 misafir</option>
            </select>
            <small>Yetiskin sayisi</small>
          </label>
          <label className="search-field span-2">
            <span className="field-caption">Oda</span>
            <select onChange={(event) => updateHotelField("room_count", event.target.value)} value={hotelForm.room_count}>
              <option value="1">1 oda</option>
              <option value="2">2 oda</option>
              <option value="3">3 oda</option>
            </select>
            <small>Oda sayisi</small>
          </label>
          <div className="search-submit">
            <button className="primary-action" type="submit">
              Otelleri goster
            </button>
            <span className="supporting-copy">Supplier-ready mock katalog ile test edilir.</span>
          </div>
        </div>
      ) : null}

      {mode === "car" ? (
        <div className="search-grid">
          <label className="search-field span-4">
            <span className="field-caption">Teslim noktasi</span>
            <input onChange={(event) => updateCarField("pickup_location", event.target.value)} value={carForm.pickup_location} />
            <small>Havalimani veya sehir</small>
          </label>
          <label className="search-field span-3">
            <span className="field-caption">Alis tarihi</span>
            <input onChange={(event) => updateCarField("pickup_date", event.target.value)} type="date" value={carForm.pickup_date} />
            <small>Araci alis gunu</small>
          </label>
          <label className="search-field span-3">
            <span className="field-caption">Birakis tarihi</span>
            <input onChange={(event) => updateCarField("dropoff_date", event.target.value)} type="date" value={carForm.dropoff_date} />
            <small>Araci teslim gunu</small>
          </label>
          <label className="search-field span-2">
            <span className="field-caption">Surucu yasi</span>
            <select onChange={(event) => updateCarField("driver_age", event.target.value)} value={carForm.driver_age}>
              <option value="25">25+</option>
              <option value="30">30+</option>
              <option value="35">35+</option>
              <option value="40">40+</option>
            </select>
            <small>Mock supplier kural testi</small>
          </label>
          <div className="search-submit">
            <button className="primary-action" type="submit">
              Araclari goster
            </button>
            <span className="supporting-copy">Gercek CarTrawler akisi icin hazir omurga.</span>
          </div>
        </div>
      ) : null}
    </form>
  );
}
