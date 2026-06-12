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

Yeni mock supplier modu ile `FLIGHT_DATA_MODE=mock_supplier` ayarinda servis, repo icindeki gercekci tedarikci katalog dosyasini normalize ederek sonuc uretir. Bu sayede:

- web ve cart akislari gercege yakin ucus verisiyle test edilir
- supplier cevabi ile ic `FlightOffer` kontrati arasinda kalici bir normalizasyon katmani korunur
- ileride gercek Duffel, Travelfusion veya Mystifly entegrasyonu geldiginde ayni endpoint ve UI korunur
- gerekirse `FLIGHT_DATA_MODE=synthetic` ile mevcut sentetik moda geri donulebilir

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
