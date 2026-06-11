# Booking Foundation

## Amaç

Odeme niyeti onaylandiktan sonra ilk rezervasyon kaydini acmak.

## Bu fazda eklenenler

- `booking-service`
- `POST /api/bookings/from-payment`
- `bookings` ve `booking_items` tabloları
- mock checkout ekranindan booking creation akisi

## Mock checkout mantigi

1. Cart uzerinden payment intent olusur
2. Mock checkout ekraninda intent gorulur
3. `paid` olarak isaretlenir
4. Booking service rezervasyon kaydi acar

## Sonraki adim

1. iyzico gercek checkout baglantisi
2. payment callback
3. booking detay sayfasi
4. email notification
