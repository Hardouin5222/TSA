export function Footer() {
  return (
    <footer className="footer">
      <div className="footer-card">
        <div>
          <span className="eyebrow">Sonraki adim</span>
          <h2>Bir sonraki sprintte auth ekranlari ve gercek API entegrasyonu gelecek.</h2>
          <p>
            Bu temel, login/register akisini, kullanici panelini ve ileride ucak arama sonucunu baglayacagimiz
            ilk profesyonel web kabugudur.
          </p>
        </div>

        <div className="footer-links">
          <a href="#search">Arama baslat</a>
          <a href="#cross-sell">Paket akisi</a>
          <a href="#routes">Rota bloklari</a>
          <a href="#experience">Deneyim prensipleri</a>
        </div>
      </div>
    </footer>
  );
}
