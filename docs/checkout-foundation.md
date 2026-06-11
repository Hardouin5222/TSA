# Checkout Foundation

## Amaç

Sepetten odeme niyeti olusturma adimina gecmek.

## Bu fazda eklenenler

- `payment-service` temel servisi
- `POST /api/payments/intents`
- `payment_intents` veri modeli
- `/cart` web ekrani
- cart -> payment intent akisi

## Bu ne saglar

- kullanicinin secimden sonra boslukta kalmamasini
- odeme oncesi server-side niyet kaydi tutulmasini
- ileride iyzico entegrasyonunun takilabilecegi net bir kontrat olusmasini

## Siradaki adim

1. cart item remove/update
2. gercek iyzico checkout url
3. payment callback / webhook
4. booking olusturma
