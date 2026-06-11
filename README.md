# Travel Super App

Bu depo, global ölçekte büyüyebilecek modüler bir Travel Super App'in temelini içerir.

## Vizyon

Tek bir platform içinde:

- Uçak bileti
- Otel rezervasyonu
- Araç kiralama
- Paket oluşturma
- Sepet
- Ödeme
- Rezervasyon yönetimi
- Kullanıcı paneli
- Admin paneli

sunacak; ilerleyen fazlarda aktiviteler, transfer, otobüs, tren, etkinlik, affiliate, B2B ve mobil deneyimle genişleyecek bir OTA ekosistemi kurulacaktır.

## Ana Teknolojiler

- Frontend: Next.js
- Mobile: Flutter
- Backend: FastAPI
- Database: PostgreSQL
- Cache: Redis
- Queue: RabbitMQ
- Object Storage: S3 uyumlu servis
- Infra: Docker, Ubuntu, Nginx

## Mimari Yaklaşım

Tam microservice yerine erken aşamada "modüler monolith + service boundaries" yaklaşımı uygulanacaktır.

Bu sayede:

- MVP daha hızlı çıkar
- Karmaşıklık gereksiz yere artmaz
- Servis sınırları baştan doğru tanımlanır
- Gerektiğinde bağımsız servislere ayrışmak kolaylaşır

## Başlangıç Servis Sınırları

- User Service
- Flight Service
- Hotel Service
- Car Rental Service
- Package Service
- Booking Service
- Payment Service
- Notification Service
- Admin Service

## Dokümantasyon

- [Ürün Yol Haritası](docs/roadmap.md)
- [Ürün ve MVP Kapsamı](docs/product-prd.md)
- [Yazılım Mimarisi](docs/architecture.md)
- [Veri Tabanı İlkeleri](docs/database-principles.md)
- [Başlangıç Backlog'u](docs/backlog.md)
- [Karar Kaydı 0001](docs/decisions/0001-platform-foundation.md)
- [Local Development](docs/local-development.md)
- [Sonraki Teknik Adım](docs/next-step-auth-foundation.md)
- [Google VM Deployment](docs/gcp-vm-deployment.md)
- [GitHub Kurulumu](docs/github-setup.md)
- [Auth Foundation](docs/auth-foundation.md)
- [Web Foundation](docs/web-foundation.md)
- [Web Auth Sprint](docs/web-auth-sprint.md)
- [Web Deployment](docs/web-deployment.md)
- [Flight Search Foundation](docs/flight-search-foundation.md)
- [Flight Results Sprint](docs/flight-results-sprint.md)
- [Cart Foundation](docs/cart-foundation.md)
- [Cart Service Foundation](docs/cart-service-foundation.md)
- [Checkout Foundation](docs/checkout-foundation.md)

## Monorepo Yapısı

Bu depo baştan itibaren monorepo olarak düzenlenir.

- `apps/web`: B2C web uygulaması
- `apps/admin`: yönetim paneli
- `apps/mobile`: Flutter mobil uygulaması
- `services/*`: domain bazlı backend servisleri
- `packages/*`: ortak kodlar
- `infra/*`: container, reverse proxy, deployment ve operasyon dosyaları
- `docs/*`: ürün, mimari ve karar kayıtları

## Çalışma Prensibi

1. Önce plan
2. Sonra mimari karar
3. Sonra klasör ve kontratlar
4. Sonra kod
5. Sonra test ve operasyonel sertleştirme

## İçinde Bulunduğumuz Aşama

Şu an: Faz 0 - Foundation / Platform Blueprint

Bu fazın amacı:

- ürün kapsamını netleştirmek
- mimari sınırları belirlemek
- veri modeli prensiplerini tanımlamak
- repo iskeletini kurmak
- ilk implementasyon sırasını çıkarmak
