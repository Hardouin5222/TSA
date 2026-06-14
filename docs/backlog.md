# Başlangıç Backlog'u

## Mevcut Aşama

Faz 0 - Foundation

## Tamamlananlar

- Ürün vizyonu netleştirildi
- Mimari yön belirlendi
- Başlangıç repo iskeleti oluşturuldu

## Sıradaki İşler

### P0

- Monorepo araç seçimi ve standartlarının belirlenmesi
- Backend foundation kurulumu
- Auth ve RBAC veri modelinin tasarlanması
- Ortak config, logging ve error handling yapısının kurulması
- Docker local development kurgusunun hazırlanması

### P1

- Kullanıcı modülü
- Admin auth
- Flight search domain modeli
- Hotel search domain modeli
- Car search domain modeli

### P2

- Cart
- Checkout
- Payment
- Booking lifecycle

## Teknik Borç Oluşturmadan İlerleme Kuralı

Yeni modül eklemeden önce aşağıdakiler hazır olmalı:

- klasör standardı
- test standardı
- migration standardı
- API response standardı
- auth standardı
- logging standardı

## Bir Sonraki Uygulama Sprinti

Önerilen sprint kapsamı:

1. Root engineering standards
2. FastAPI backend foundation
3. PostgreSQL, Redis, RabbitMQ, Nginx, object storage local setup
4. Auth module başlangıcı
