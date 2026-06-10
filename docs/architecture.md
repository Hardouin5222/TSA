# Yazılım Mimarisi

## Mimari Karar

Başlangıçta önerilen yaklaşım:

**Modüler monolith + net service boundaries**

Bu, doğrudan dağıtık microservice ile başlamaktan daha doğru seçimdir çünkü:

- MVP hızını korur
- DevOps yükünü erken aşamada patlatmaz
- Veri tutarlılığı daha kolay yönetilir
- Domain sınırları doğru tanımlanır
- Sonradan servis ayrıştırma mümkündür

## Hedef Durum

İş alanları baştan bağımsız modüller olarak tasarlanır. Kod tabanında ve veri modelinde servis sınırları korunur. Trafik, ekip büyüklüğü veya bağımsız deploy ihtiyacı oluştuğunda bu modüller ayrı servislere bölünebilir.

## Önerilen Üst Seviye Yapı

### İstemciler

- Web app: Next.js
- Admin app: Next.js
- Mobile app: Flutter

### Backend katmanları

- API Gateway / BFF
- Domain services
- Provider integration adapters
- Shared platform modules

### Veri ve altyapı

- PostgreSQL
- Redis
- RabbitMQ
- S3 compatible object storage

## Domain Modülleri

### 1. Identity and Access

- kullanıcı
- rol
- yetki
- oturum
- refresh token
- cihaz / login geçmişi

### 2. Catalog and Search

- flight search
- hotel search
- car search
- provider response normalization
- filter / sort

### 3. Cart and Checkout

- sepet
- fiyat doğrulama
- checkout session
- ödeme hazırlığı

### 4. Booking

- booking kayıtları
- booking item
- traveller / guest
- status lifecycle
- cancel / refund temel modeli

### 5. Payments

- payment intent
- ödeme denemeleri
- callback / webhook yönetimi
- mutabakat için transaction log

### 6. Notifications

- email
- sms
- push
- sistem içi bildirim

### 7. Admin and Operations

- operasyon ekranları
- sağlayıcı hata görünürlüğü
- destek notları
- audit ve manuel işlem kayıtları

## Provider Integration Katmanı

Sağlayıcı entegrasyonları domain katmanından doğrudan çağrılmamalıdır. Her sağlayıcı bir adapter arkasında soyutlanmalıdır.

Örnek:

- `flight_service/providers/duffel`
- `flight_service/providers/travelfusion`
- `flight_service/providers/mystifly`

Bu yaklaşım:

- sağlayıcı değişimini kolaylaştırır
- failover stratejilerine imkan tanır
- test yazmayı kolaylaştırır
- domain kodunu temiz tutar

## API Stratejisi

Başlangıçta:

- İç servisler REST tabanlı olabilir
- Dış istemcilere BFF veya API gateway üstünden erişim verilir

İleride:

- yoğun okuma senaryolarında GraphQL BFF düşünülebilir
- event-driven entegrasyonlar artırılabilir

## Güvenlik Standartları

- JWT access token
- Refresh token rotation
- RBAC
- rate limit
- brute force koruması
- audit log
- PII alanları için kontrollü erişim
- secret management

## Operasyonel Standartlar

- Structured logging
- Correlation ID
- Merkezi hata formatı
- Healthcheck endpointleri
- OpenAPI sözleşmeleri
- Docker tabanlı local development
- CI ile lint + test + migration kontrolü

## Ayrıştırma Stratejisi

İlk ayrışabilecek modüller:

1. User / Auth
2. Booking
3. Payment
4. Notification

Arama modülleri ise dış sağlayıcılara bağımlılık sebebiyle kendi entegrasyon yüküne sahip olacağından başlangıçtan itibaren sınırları daha sıkı tutulmalıdır.
