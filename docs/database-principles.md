# Veri Tabanı İlkeleri

## Ana Kararlar

- Ana veritabanı: PostgreSQL
- Birincil anahtar: UUID
- Tüm ana tablolarda `created_at`, `updated_at`
- Gereken tablolarda `deleted_at` ile soft delete
- Kritik hareketlerde audit log
- Yoğun sorgulanan alanlarda indeks

## Tasarım İlkeleri

### 1. Domain bazlı şema düşüncesi

Tek veritabanı içinde mantıksal ayrım korunur:

- `identity`
- `flight`
- `hotel`
- `car`
- `package`
- `cart`
- `booking`
- `payment`
- `admin`
- `audit`

Not: Fiziksel olarak ayrı schema kullanımı değerlendirilebilir. Başlangıçta tek public schema + isimlendirme standardı ile başlamak mümkündür. Ancak domain ayrımı kod ve migration düzeyinde korunmalıdır.

### 2. Tutarlılık

- Booking ve payment verileri için transaction disiplini zorunlu
- Sağlayıcı cevapları ham haliyle gerektiğinde saklanmalı
- Kritik durum geçişleri event veya history tablosu ile izlenmeli

### 3. Ölçeklenebilirlik

- Sık filtrelenen kolonlara composite index
- Booking, payment ve audit gibi yüksek hacimli alanlarda ileride partition planı
- Okuma performansı için gerektiğinde materialized view veya read model

### 4. Güvenlik

- Hassas alanlar maskeleme kurallarıyla işlenmeli
- Kart verisi tutulmamalı
- Kişisel veriler KVKK/GDPR perspektifiyle sınıflandırılmalı

## Her Ana Tabloda Beklenen Alanlar

- `id UUID PK`
- `created_at TIMESTAMPTZ NOT NULL`
- `updated_at TIMESTAMPTZ NOT NULL`
- `deleted_at TIMESTAMPTZ NULL` gerekli ise
- `created_by UUID NULL` gerekli ise
- `updated_by UUID NULL` gerekli ise
- `version INT` gerekli optimistic locking senaryolarında

## İlk Tasarlanacak Çekirdek Tablolar

1. users
2. roles
3. permissions
4. user_sessions
5. travellers
6. carts
7. cart_items
8. bookings
9. booking_items
10. payments
11. payment_transactions
12. audit_logs
13. provider_requests
14. provider_responses

## Kritik Uyarı

Flight, hotel ve car ürünleri birbirinden farklı veri yapısına sahiptir. Bu yüzden tek bir "universal product table" ile başlamak yerine ortak booking çekirdeği + ürün bazlı detay tabloları yaklaşımı daha profesyoneldir.
