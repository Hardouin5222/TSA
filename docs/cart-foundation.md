# Cart Foundation

## Amaç

Ucus sonuc ekranindan kullanici secimini checkout'a yaklastiran ilk sepet hissini olusturmak.

## Bu fazda eklenenler

- secili teklif ozeti
- filtre ve siralama katmani
- teklif detay sinyalleri
- `Sepete ekle foundation` CTA mantigi
- `cart-service` backend servisi
- `POST /api/cart/items/flight`
- `GET /api/cart/current`
- guest session veya login token ile cart baglama

## Bilincli olarak henuz olmayanlar

- offer lock / repricing
- checkout session
- cart item silme / guncelleme
- coklu urun merge mantigi

## Sonraki backend sprinti

1. `cart-service` iskeleti
2. cart item silme ve guncelleme
3. checkout session
4. offer repricing
5. payment-service baglantisi
