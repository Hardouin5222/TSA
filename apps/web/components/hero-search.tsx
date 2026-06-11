export function HeroSearch() {
  return (
    <section className="hero-search" id="search" aria-label="Main search">
      <div className="journey-toggle">
        <button className="is-selected" type="button">
          Gidis - donus
        </button>
        <button type="button">Tek yon</button>
        <button type="button">Coklu ucus</button>
      </div>

      <div className="search-grid">
        <div className="search-field span-3">
          <label>Nereden</label>
          <strong>Istanbul</strong>
          <span>IST • Sabiha ve Istanbul Havalimani</span>
        </div>
        <div className="search-field span-3">
          <label>Nereye</label>
          <strong>Antalya</strong>
          <span>AYT • Lara, Konyaalti, Kemer baglantili</span>
        </div>
        <div className="search-field span-2">
          <label>Gidis</label>
          <strong>18 Temmuz Cum</strong>
          <span>Sabah kalkislarinda daha dusuk fiyat</span>
        </div>
        <div className="search-field span-2">
          <label>Donus</label>
          <strong>22 Temmuz Sal</strong>
          <span>4 gece en populer secim</span>
        </div>
        <div className="search-field span-2">
          <label>Yolcu</label>
          <strong>2 yetiskin</strong>
          <span>1 oda veya paket eslestirilebilir</span>
        </div>
        <div className="search-submit">
          <button className="primary-action" type="button">
            Fiyatlari goster
          </button>
          <span className="supporting-copy">3 tik akisi: ara, sec, satin al.</span>
        </div>
      </div>
    </section>
  );
}
