# Flight Search Foundation

## Amaç

Ucus arama deneyimini frontend landing ekranindan backend service contract'ina kadar ilk kez gercek akisa baglamak.

## Bu fazda eklenenler

- `flight-service` temel FastAPI servisi
- `POST /api/flights/search` endpoint'i
- normalize ucus teklif modeli
- `api-gateway` flight search proxy akisi
- web landing search formundan `/flights` sonuc ekranina gecis

## Mimari not

Bu asamada sonuc listesi saglayici baglantili degil, ama veri kontrati saglayici-agnostic olacak sekilde kuruldu.

Bu bize sonraki sprintte:

- Duffel adapteri
- Travelfusion adapteri
- Mystifly adapteri
- cache ve fiyat dogrulama

eklerken UI ve gateway kontratini bozmadan ilerleme imkani verir.

## Sonraki adim

1. flight provider adapter katmani
2. sonuc filtreleme ve sort modeli
3. detay sayfasi
4. sepete ekleme baslangici
