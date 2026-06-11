const highlights = [
  {
    title: "Mobil once gelir",
    text: "CTA alanlari bas parmak erisimi dusunulerek yerlestirilir, form yogunlugu azaltilir.",
    labels: ["Sticky CTA", "Tek odak", "Hizli gecis"],
  },
  {
    title: "Guven sinyalleri erken gorunur",
    text: "Iade, odeme, fiyat dogrulugu ve destek mesajlari checkout'tan once algilanir.",
    labels: ["Guvenli odeme", "Acik fiyat", "Destek"],
  },
  {
    title: "Ikon ve illustasyon dili butunluklu",
    text: "Kartlar, yonler, baglanti durumlari ve hizmet tipleri tek bir ikon seti mantigiyla ele alinir.",
    labels: ["Tutarlilik", "Yalin ikonlar", "Marka hissi"],
  },
];

export function ExperienceHighlights() {
  return (
    <section className="section-card" id="experience">
      <h2>Kullanicinin kafasini karistirmayan, profesyonel bir deneyim dili.</h2>
      <p>
        UI hedefimiz sadece guzel gorunmek degil; arama niyetini satin almaya donusturen net bir yol sunmak.
      </p>

      <div className="highlight-grid">
        {highlights.map((item) => (
          <article className="highlight-card" key={item.title}>
            <h3>{item.title}</h3>
            <p>{item.text}</p>
            <div className="highlight-list">
              {item.labels.map((label) => (
                <span key={label}>{label}</span>
              ))}
            </div>
          </article>
        ))}
      </div>
    </section>
  );
}
