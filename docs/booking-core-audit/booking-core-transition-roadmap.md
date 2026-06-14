# Booking Core Hibrit Gecis Yol Haritasi

## Amaç

Bu belge, `Booking Core 4.0.1` paketini hangi sinirlar icinde kullanacagimizi ve `TSA` vizyonunu bozmadan nasil hiz kazanacagimizi sabitler.

Ana prensip:

- Booking Core = hizlandirici omurga
- TSA domain mantigi = asil urun kontrolu
- Supplier API entegrasyonlari = bizim yonettigimiz adapter katmani
- Turna benzeri UX = bizim sahip oldugumuz urun katmani

Bu nedenle hedefimiz "hazir paketi oldugu gibi yayina almak" degil, "hazir omurgayi kontrollu sekilde kullanip gercek OTA urunune donusturmek"tir.

## Nihai Karar

Secilen model:

**Kontrollu hibrit gecis**

Yani:

- kullanici, booking, siparis, temel panel ve payment gateway iskeleti gibi hiz kazandiran alanlarda Booking Core'dan yararlanacagiz
- supplier verisi, search normalization, fiyat dogrulama, sepet kurallari, checkout deneyimi ve OTA operasyon mantigini bizim kurallarimizla yonetecegiz
- kullaniciya gorunen ekranlarda "gercek veri varsa goster, yoksa gizle" politikasi uygulanacak

## Neyi Alıyoruz

### 1. Kullanici ve kimlik omurgasi

Booking Core'dan alinabilecekler:

- kayit / giris
- sifre sifirlama
- temel profil
- kullanici panel iskeleti
- admin auth ve panel omurgasi

Not:

- uzun vadede JWT / API-first auth ihtiyaci devam edecegi icin bu katman daha sonra ayrisabilecek sekilde tutulacak

### 2. Booking ve order omurgasi

Booking Core'dan alinabilecekler:

- booking kaydi
- siparis / order mantigi
- durum yonetimi icin temel iskelet
- rezervasyon gecmisi
- yonetim paneli uzerinden rezervasyon goruntuleme

Not:

- booking kaydinin ic veri modeli TSA icin zenginlestirilecek
- supplier record locator, fare basis, ticketing status, refundability, repricing sonucu gibi alanlar sonradan eklenecek

### 3. Payment gateway extension iskeleti

Booking Core'dan alinabilecekler:

- gateway kayit yapisi
- odeme akisi icin temel controller yapisi
- callback / result akisi icin extension noktasi

Bizim yazacaklarimiz:

- iyzico gateway
- sonrasinda PayTR veya Stripe
- OTA odemeye ozel audit / transaction log
- timeout, retry, callback failover mantigi

### 4. Admin / operasyon hizlandiricilari

Booking Core'dan alinabilecekler:

- yonetim paneli menuleri
- temel CRUD alanlari
- kullanici / booking / order listeleme
- kupon / fee / sabit konfigurasyon mantiklari

Bizim yazacaklarimiz:

- operasyon ekibi icin OTA odakli ekranlar
- supplier hata gorunurlugu
- repricing / hold / ticketing durumlari
- manuel mudahale notlari

## Neyi Kapatıyoruz veya Sinirli Kullanıyoruz

### 1. Local inventory mantigi

Booking Core flight/hotel/car modulleri varsayilan olarak local veritabani merkezli calisiyor.

Bu alanlarda:

- supplier-native arama mantigi dogrudan yok
- fare ailesi, anlik fiyat, availability, hold süresi gibi veriler gercek OTA mantiginda supplier'dan gelir

Karar:

- local inventory mantigi ana gercek kabul edilmeyecek
- supplier'dan gelen normalized teklif verisi ana kaynak olacak
- gerekiyorsa Booking Core tarafinda sadece "cache / mirror / booking snapshot" mantigi kullanilacak

### 2. Varsayilan checkout formu

Mevcut genel booking checkout'u direkt kullanmayacagiz.

Sebep:

- fazla genel
- OTA kullanicisina gore dağinik
- Turna benzeri 3 tik mantigina uygun degil

Karar:

- checkout deneyimi TSA tarafinda yeniden tasarlanacak
- Booking Core burada sadece veri kaydetme ve odeme gecisi omurgasi olarak hizmet edecek

### 3. Kullaniciya gereksiz alan gosterimi

Koltuk secimi, yemek secimi, SSR, loyalty gibi alanlar:

- supplier API gercekten donuyorsa gosterilecek
- supplier verisi yoksa tamamen gizlenecek

Bu alanlar asla "mock yuzunden hep gorunen kalabalik alanlar" olmayacak.

## Bizim Asil Yazacagimiz Katman

## 1. Supplier Adapter Layer

Bu proje icin kritik cekirdek burada.

Her dikey icin ayri adapter mantigi kurulacak:

- `flight/providers/duffel`
- `flight/providers/travelfusion`
- `flight/providers/mystifly`
- `hotel/providers/hotelbeds`
- `hotel/providers/expedia_rapid`
- `car/providers/cartrawler`

Adapter sorumluluklari:

- request builder
- auth / token management
- response parser
- normalized offer model
- error mapping
- timeout / retry stratejisi
- supplier capability matrix

### Capability matrix mantigi

Her supplier icin sistem sunu bilecek:

- seat_selection_supported
- meal_selection_supported
- baggage_upsell_supported
- refund_rules_supported
- branded_fares_supported
- hold_supported
- ticketing_flow_supported

Boylece UI "varsa goster, yoksa gizle" kuralini saglikli sekilde uygulayacak.

## 2. Normalized OTA Domain Model

Booking Core'un ic modeline ek olarak TSA tarafinda normalize alanlar standardize edilecek.

### Flight offer

- provider
- provider_offer_id
- validating_airline
- marketing_airline
- operating_airline
- origin
- destination
- departure_at
- arrival_at
- stop_count
- duration_minutes
- baggage_items
- cabin_class
- fare_family
- fare_rules_summary
- cancellable
- changeable
- branded_fare_options
- ancillary_options
- total_price
- currency
- expires_at

### Checkout intent

- selected_offer_snapshot
- selected_package_snapshot
- traveller_requirements
- contact_requirements
- invoice_requirements
- supported_optional_services

### Booking snapshot

- supplier_booking_reference
- pnr / locator
- payment_reference
- ticketing_status
- notification_status

## 3. Turna Benzeri UX Katmani

Burada hedef "sadece guzel gorunmek" degil, satin almayi hizlandirmak.

Ana UX kurallari:

- filtreler solda, sonuc kartlari ortada, destekleyici panel sagda
- fiyat ve secim butonu tek bakista gorulecek
- paket secimi modal veya step icinde net olacak
- checkout tek uzun form gibi degil, adim mantigi ile sade olacak
- sag panel her zaman canli fiyat / secim / toplam durumunu gosterecek
- gereksiz alanlar varsayilan olarak kapali veya gizli olacak

### UI ilke karari

- Turna'ya benzer akis mantigi alinabilir
- ama birebir kopya urun degil, TSA marka diliyle uygulanacak
- renk omurgasi yesil-beyaz-siyah dokunuslarla korunacak

## Modül Bazli Karar Matrisi

## Booking Core'dan kullan

- user auth temel akisi
- admin panel omurgasi
- order / booking kaydi temel mantigi
- payment gateway extension altyapisi
- notification gonderim iskeleti
- kupon / fee gibi yardimci altyapilar

## Booking Core'u override et

- flight search sonucu
- flight detail / package secimi
- checkout ekranlari
- cart experience
- booking detail customer view
- account reservation experience

## Booking Core'u ana gercek olarak kabul etme

- local flight inventory
- local hotel inventory
- local car inventory
- supplier capability varsayimlari
- eski nesil generic checkout alanlari

## Sprint Sirasi

## Sprint 1 - Teknik Omurga Karari

Hedef:

- Booking Core hibrit modelini repo seviyesinde resmilestirmek
- supplier adapter mimarisini klasor ve kontrat bazinda tanimlamak
- hangi ekranlarin Blade, hangi entegrasyonlarin servis katmani uzerinden ilerleyecegini netlestirmek

Teslim:

- karar belgeleri
- modül sorumluluklari
- capability matrix taslagi

## Sprint 2 - Flight Gercekcilik Katmani

Hedef:

- mock supplier switch sistemi
- normalize flight offer modeli
- package/fare family secimi
- gercek veri yoksa alan gizleme mantigi

Teslim:

- gercege yakin flight search
- package secim modal akisi
- Turna benzeri sonuc kartlari

## Sprint 3 - Checkout Son Kullanici Seviyesi

Hedef:

- yolcu
- iletisim
- fatura
- sadece supplier destekliyorsa ek hizmetler
- odeme ozet paneli

Teslim:

- kullanici dostu checkout
- validasyonlar
- eksik alan durumlarinda net uyari akisi

## Sprint 4 - Payment Production Skeleton

Hedef:

- iyzico gercek entegrasyon iskeleti
- callback / result handling
- payment + booking status mutabakati

Teslim:

- prod'a yakin odeme akisi
- hata senaryolari
- audit log

## Sprint 5 - Hotel ve Car Dikeyleri

Hedef:

- ayni supplier adapter mantigini hotel ve car'a tasimak
- ortak search/result/cart/checkout kural setini korumak

Teslim:

- yatay olarak tutarli super app deneyimi

## Kullaniciya Gosterim Kurali

Bu proje icin zorunlu urun kurali:

### Goster

- supplier acikca destekliyorsa
- veri gercekten response icinde geliyorsa
- fiyat veya operasyonel etkisi net ise

### Gizle

- mock ama gercekte supplier tarafinda garanti edilmeyen alanlar
- check-in sonrasi havayolu tarafinda yonetilen secenekler
- sadece operasyon icin anlamli ama son kullanici icin gürültu ureten alanlar

### Sonuc

Koltuk secimi / yemek secimi / ek hizmetler:

- supplier destekliyorsa goster
- desteklemiyorsa hic gosterme
- "yakinda" veya "demo" alanlarini checkout ana akisina tasima

## Mimari Kirmizi Cizgiler

- TSA supplier adapter mantigi Booking Core icine gomulup kaybolmayacak
- normalized domain model korunacak
- UI supplier'dan bagimsiz sekilde capability matrix ile beslenecek
- payment entegrasyonu production-ready dusunulecek
- ileride Laravel'den ayirma ihtimali kapanmayacak

## Su An Icin Benden Sonraki Teknik Is

Bir sonraki uygulama adimi:

1. Booking Core hibrit kullanim klasor / entegrasyon haritasini cikarmak
2. supplier adapter kontratlarini repo icinde tanimlamak
3. Turna benzeri flight result + package selection akisini bu kontrata baglamak
4. checkout ekranini "gercek veri varsa goster" mantigiyla sadeleştirmek

## Senden Gerekecekler

Bu asamada zorunlu bir is yok.

Yakinda ihtiyac olabilecekler:

- varsa Booking Core lisans / kurulum notlari
- iyzico sandbox bilgileri
- elindeki ek mock supplier response ornekleri
- varsa hotel ve car icin daha fazla gercek response örnegi

## Son Not

Bu karar, mevcut TSA planini iptal etmez.

Tam tersine:

- zamani kisaltir
- tekrari azaltir
- ama kritik alanlarda kontrolu bizde tutar

Yani yeni rota:

**"Sifirdan her seyi yazmak" degil, "stratejik olarak hiz kazanip OTA cekirdegini bizim kurallarimizla insa etmek".**
