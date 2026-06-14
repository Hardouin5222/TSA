# Booking Core 4.0.1 Fit Report

## Amaç

Bu inceleme, `Booking Core 4.0.1` paketinin mevcut `TSA` vizyonuna ne kadar uyduğunu ve hangi modelle kullanılmasının en doğru olacağını netleştirmek için yapıldı.

Hedef sorular:

1. Bunu ana ürün omurgası yapabilir miyiz?
2. Hangi parçaları hazır alabiliriz?
3. Hangi parçaları yeniden yazmamız gerekir?
4. Turna.com benzeri sade, güçlü OTA deneyimini bu yapı üstünde kurmak mantıklı mı?

## İncelenen Yapı

Arşiv açıldı ve temel klasörler incelendi:

- `bc-cms/`
- `public_html/`
- `modules/`
- `themes/`
- `plugins/`

Özellikle şu alanlar denetlendi:

- `modules/Flight`
- `modules/Hotel`
- `modules/Car`
- `modules/Booking`
- `modules/Order`
- `modules/Api`
- `themes/BC`

## Net Bulgular

### 1. Bu paket büyük bir Laravel monolith

Bu ürün küçük bir booking engine değil. İçinde geniş kapsamlı bir monolith var:

- kullanıcı
- booking
- order
- coupon
- vendor
- agency
- hotel
- flight
- car
- visa
- sms
- cms/theme

Bu bize hız kazandırır, ama mimari bağımlılığı da artırır.

### 2. Frontend override edilebilir

`themes/BC` altında flight ve booking sayfaları tema override mantığıyla ayrılmış durumda.

Örnekler:

- `themes/BC/Flight/Views/frontend/search.blade.php`
- `themes/BC/Flight/Views/frontend/layouts/search/list-item.blade.php`
- `themes/BC/Flight/Views/frontend/layouts/search/modal-form-book.blade.php`
- `themes/BC/Booking/Views/frontend/checkout.blade.php`

Sonuç:

- Turna benzeri arayüz yapılabilir
- Blade/CSS/JS tarafında ciddi özelleştirme mümkün
- ama bu sistem `Next.js` tarzı bağımsız frontend değil, Laravel tema mantığıyla ilerliyor

### 3. Flight modülü supplier API odaklı değil, kendi veritabanı odaklı

`modules/Flight/Controllers/FlightController.php` ve `modules/Flight/Models/Flight.php` incelendi.

Mevcut davranış:

- search, local veritabanı sorgusuna dayanıyor
- detay modalı local `Flight`, `FlightSeat`, `Airport`, `Airline` ilişkilerinden besleniyor
- add-to-cart akışı supplier response değil, sistem içi booking nesnesi oluşturuyor

Yani bu sistem doğrudan:

- Duffel
- Travelfusion
- Mystifly

gibi gerçek OTA supplier akışları için tasarlanmamış.

Kurulabilir, ama bunun için adapter katmanı ve veri eşleme mantığını bizim yazmamız gerekir.

### 4. Payment mimarisi genişletilebilir

`modules/Booking/ModuleProvider.php` içinde gateway kayıt sistemi var.

Mevcut örnekler:

- offline
- paypal
- stripe
- paystack
- payrexx

Yani:

- `iYZICO`
- `PayTR`

entegrasyonu teknik olarak yapılabilir.

Bu iyi haber, çünkü gateway mantığı soyutlanmış durumda.

### 5. Checkout akışı klasik booking form mantığında

`modules/Booking/Views/frontend/booking/checkout-form.blade.php` ve
`modules/Booking/Controllers/BookingController.php` incelendi.

Varsayılan checkout:

- ad
- soyad
- email
- telefon
- adres
- şehir
- ülke
- müşteri notu
- payment gateway

Bu yapı bizim istediğimiz sade OTA checkout için fazla genel ve biraz eski nesil.

Ama önemli nokta:

- passenger save mantığı override edilebilir
- service bazlı `filterPassengerData`, `beforeCheckout`, `afterCheckout` hook'ları var

Bu sayede uçuş özel akışı kurmak mümkün.

### 6. Passenger yapısı temel seviyede

`modules/Booking/Models/BookingPassenger.php` içinde varsayılan alanlar:

- first_name
- last_name
- email
- phone
- dob
- id_card
- seat_type
- price

Bu iyi bir başlangıç.

Ama gerçek OTA için ek ihtiyaçlar olabilir:

- nationality
- passport_no
- passport_expiry
- gender
- SSR/OSI
- loyalty program
- fare rule acceptance

Bunlar ek geliştirilebilir.

### 7. Hotel / Car modülleri de local inventory mantığında

İncelemede flight/hotel/car modüllerinde supplier connector mantığına dair yerleşik bir yapı görünmedi.

`Http::`, `curl`, harici provider client gibi doğrudan entegrasyon kalıpları modüllerde tespit edilmedi.

Bu şu anlama gelir:

- hotel ve car için de supplier adapter katmanını bizim kurmamız gerekecek
- yani paket bizi panel ve akış tarafında hızlandırır, ama supplier entegrasyonunda otomatik çözüm sunmaz

## TSA Planına Uyum Değerlendirmesi

### Güçlü Yanlar

- hazır kullanıcı sistemi
- hazır booking/order altyapısı
- admin ve vendor panelleri
- coupon/fee/deposit mantığı
- tema override sistemi
- payment gateway extension mantığı
- flight/hotel/car için temel CRUD ve görüntüleme iskeleti

### Zayıf Yanlar

- monolith yapı
- modern modüler servis mimarisinden uzak
- supplier-native OTA tasarımı yok
- varsayılan checkout fazla genel
- frontend tarafı Blade/tema mantığına bağlı
- veri modeli kendi local inventory mantığını merkez alıyor

## Karar Matrisi

### Seçenek A: Tam pivot

Booking Core ana omurga olur, TSA bunun üstüne taşınır.

Artı:

- en hızlı başlangıç
- çok sayıda temel özellik hazır

Eksi:

- mevcut FastAPI/Next planından kopuş
- vendor koduna güçlü bağımlılık
- supplier API entegrasyonları daha zor özelleşebilir
- uzun vadede kontrol kaybı riski

Karar:

- kısa vadede hızlı
- uzun vadede riskli

### Seçenek B: Hibrit kullanım

Booking Core, temel booking/panel/admin omurgası olarak değerlendirilir; supplier adapter, ödeme ve OTA UX tarafı bizim tarafımızdan yeniden tasarlanır.

Artı:

- hız + kontrol dengesi
- hazır alanlardan yararlanma
- Turna benzeri akışları kademeli kurma imkanı

Eksi:

- uyarlama katmanı ister
- bazı modülleri devre dışı bırakmak gerekir

Karar:

- en mantıklı seçenek

### Seçenek C: Mevcut TSA ile sıfırdan devam

Artı:

- tam kontrol
- temiz mimari

Eksi:

- daha uzun takvim

Karar:

- teknik olarak en temiz
- ama hız baskısında yavaş kalabilir

## Önerilen Yol

Bu paket kullanılabilir, ama `tam teslimiyetle ana sistem` yapılmamalı.

Önerilen model:

1. Booking Core ayrı bir çalışma hattında kurulmalı
2. Uçuş, otel, araç supplier adapter katmanı bizim tarafımızda yazılmalı
3. Turna benzeri frontend deneyimi tema override ile sadeleştirilmeli
4. Kullanılmayacak modüller kapatılmalı
5. Mock ve real supplier arasında aç/kapat mantığı kurulmalı
6. Türkiye ödeme entegrasyonu özel gateway olarak eklenmeli

## Sonuç

Booking Core:

- bizi hızlandırır
- ama bizi otomatik olarak modern OTA yapmaz

Doğru kullanım şekli:

- hazır iskeleti almak
- supplier, ödeme ve UX çekirdeğini bizim tasarlamamız

Bu nedenle nihai öneri:

`Full pivot` değil, `kontrollü hibrit pivot`.

## Sonraki Adım

Eğer bu yön seçilirse sıradaki iş:

1. Booking Core için bir entegrasyon planı çıkarmak
2. hangi modüllerin kullanılacağını işaretlemek
3. hangi modüllerin kapatılacağını belirlemek
4. flight/hotel/car supplier adapter mimarisini çizmek
5. iYZICO/PayTR gateway tasarımını çıkarmak
6. Turna benzeri 3 adımlı flow'u Blade düzeyinde yeniden kurmak
