const routes = [
  {
    title: "Istanbul → Antalya",
    summary: "Yaz sezonu icin ucak + otel + transfer oneri akisi.",
    tags: ["En populer", "Aile", "4 gece paketi"],
  },
  {
    title: "Istanbul → Izmir",
    summary: "Hafta sonu kacamaklari icin hizli karar veren segment.",
    tags: ["Sehir kacisi", "Kisa konaklama", "Arac ekleme"],
  },
  {
    title: "Ankara → Dubai",
    summary: "Yuksek sepet potansiyelli yurt disi bundle kurgusu.",
    tags: ["Yurt disi", "Vize bilgisi", "Premium oteller"],
  },
];

export function PopularRoutes() {
  return (
    <section className="section-card" id="routes">
      <h2>Ilk ekranda satin alma niyetini yakalayan rota setleri.</h2>
      <p>
        Her rota yalnizca fiyat gostermekle kalmaz; kullaniciya sonraki en mantikli urunu da hazir eder.
      </p>

      <div className="route-grid">
        {routes.map((route) => (
          <article className="route-card" key={route.title}>
            <h3>{route.title}</h3>
            <p>{route.summary}</p>
            <div className="route-meta">
              {route.tags.map((tag) => (
                <span key={tag}>{tag}</span>
              ))}
            </div>
          </article>
        ))}
      </div>
    </section>
  );
}
