# Web Auth Sprint

## Amaç

Landing temeli ustune gercek kullanici giris akisini oturtmak.

## Bu fazda eklenenler

- `/login` sayfasi
- `/register` sayfasi
- `/forgot-password` sayfasi
- `/account` sayfasi
- frontend API istemcisi
- local session saklama yardimcilari
- auth formlari ve backend endpoint entegrasyonu

## Backend entegrasyon noktasi

Web uygulamasi su endpointleri kullanir:

- `POST /api/auth/login`
- `POST /api/auth/register`
- `POST /api/auth/password-reset/request`
- `GET /api/users/me`

## Cevaplanan UX ihtiyaclari

- mobilde hizli giris
- net CTA hiyerarsisi
- kayit sonrasi dogrudan hesap alanina gecis
- hata mesaji gosterebilen form yapisi

## Sonraki frontend sprinti

1. server-side session ve cookie tabanli auth
2. refresh token yenileme akisi
3. header'da aktif kullanici durumu
4. booking arama formunun gercek backend kontratiyla baglanmasi
5. admin web kabugu
