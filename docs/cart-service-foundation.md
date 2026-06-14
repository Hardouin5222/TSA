# Cart Service Foundation

## Amaç

Ucus sonucu secimini kalici backend sepetine yazmak.

## Endpointler

- `POST /api/cart/items/flight`
- `GET /api/cart/current`

## Veri modeli

- `carts`
- `cart_items`

## Calisma mantigi

- Login olan kullanici varsa JWT icindeki `sub` ile user-bound cart
- Login yoksa `guest_session_id` ile guest cart
- Ayni offer tekrar eklenirse item guncellenir

## Sonraki adim

1. item remove/update
2. cart summary endpoint genisletme
3. checkout hazirligi
