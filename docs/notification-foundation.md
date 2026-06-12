# Notification Foundation

## Amac

Rezervasyon sonrasi email, sms ve push akislari icin provider bagimsiz bir notification kayit omurgasi kurmak.

## Bu fazda eklenenler

- `notification-service`
- `POST /api/notifications/booking-confirmations`
- `GET /api/notifications`
- `GET /api/notifications/{notification_id}`
- `notifications` tablosu
- booking-service icinden booking confirmation notification olusturma
- booking confirmation template render temeli
- booking detail ekraninda notification icerigi gorunurlugu
- provider adapter temeli (`mock` aktif, diger providerlar icin extension point hazir)

## Mimari Karar

Bu ilk versiyonda notification olusumu, booking olusumunu bloklamaz.

- Booking basariliysa rezervasyon kaydi acilir
- Notification servisine best-effort cagri yapilir
- Notification kaydi olusursa sistemde izlenebilir hale gelir
- Notification tarafinda hata olsa bile booking geri alinmaz

Bu karar operasyonel olarak dogrudur cunku odeme ve rezervasyon kaydini email gonderimiyle birbirine baglamak risklidir.

## Status Mantigi

- `queued`: recipient bilgisi hazir, gonderime uygun
- `pending_recipient`: recipient bilgisi eksik, daha sonra enrich edilecek
- `sent`: provider dispatch tamamlandi
- `failed`: provider secili ama dispatch basarisiz

## Provider Yapisi

Bu servis artik iki katmanla calisir:

- notification kaydini olusturma
- provider adapter uzerinden dispatch etme

Ilk aktif provider `mock` olarak gelir. Bu sayede urun akisi bozulmadan:

- booking confirmation kaydi uretilir
- dispatch adiminda provider abstraction kullanilir
- yarin `smtp`, `resend` veya `sendgrid` adapteri eklendiginde router ve UI degismez

## Yeni Env Degerleri

- `NOTIFICATION_PROVIDER=mock`
- `NOTIFICATION_SENDER_NAME=Travel Super App`
- `NOTIFICATION_SENDER_EMAIL=noreply@travel-super-app.local`
- `NOTIFICATION_MOCK_REFERENCE_PREFIX=mocknotif`

## Sonraki Adim

1. RabbitMQ consumer ile async dispatch
2. SMTP veya Resend adapter implementasyonu
3. Iyzico callback sonrasi confirmation trigger
4. SMS ve push kanallari
5. Admin panel notification log ekrani
