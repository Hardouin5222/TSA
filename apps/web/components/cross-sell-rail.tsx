const cards = [
  {
    eyebrow: "Bundle Boost",
    title: "Ucaktan sonra otel ekle, sepet ortalamasini yukari cek.",
    copy: "Rezervasyon niyetini bozmadan, ayni destinasyonda filtrelenmis otel onerileriyle ikinci urunu dogal sekilde one cikariyoruz.",
    price: "1.450 TL’den baslayan ek oteller",
  },
  {
    eyebrow: "Mobil Dönüsüm",
    title: "Kisa sureli karar veren kullanici icin akisi sade tut.",
    copy: "Tek ekranda temel alanlar, altinda guven sinyalleri ve sabit CTA ile mobil kullaniciyi yormayan bir kurgu.",
    price: "3 tik mantigina uygun kisa akis",
  },
  {
    eyebrow: "Paket Hazir",
    title: "Antalya yaz paketi gibi niyeti yuksek kombinasyonlari one cikar.",
    copy: "Ucak + otel + arac gibi yuksek degerli bundle bloklari landing icinde erken gorunur olur.",
    price: "Paket oranini artirmaya uygun",
  },
];

export function CrossSellRail() {
  return (
    <section className="section-card" id="cross-sell">
      <h2>Capraz satisi zorlamayan, kullaniciya dogal gelen bloklar.</h2>
      <p>
        Turna benzeri hizli arama hissi, Enuygun benzeri profesyonel guven duygusu ve Odamax benzeri
        donusum mantigini ayni landing kurgusunda birlestiriyoruz.
      </p>

      <div className="cross-sell-rail">
        {cards.map((card) => (
          <article className="cross-sell-card" key={card.title}>
            <div className="cross-sell-meta">
              <span className="pill">{card.eyebrow}</span>
              <span className="price">{card.price}</span>
            </div>
            <h3>{card.title}</h3>
            <p>{card.copy}</p>
            <span className="card-cta">Detayli akisa gec</span>
          </article>
        ))}
      </div>
    </section>
  );
}
