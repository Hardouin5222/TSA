# Yol Haritası

## Mevcut Aşama

Faz 0 - Foundation / Platform Blueprint

## Faz 0 Hedefleri

- Ürün kapsamını netleştirmek
- MVP modüllerini kesinleştirmek
- Modüler mimari sınırlarını tanımlamak
- Monorepo yapısını kurmak
- Temel güvenlik ve platform standartlarını belirlemek
- İlk uygulama backlog'unu çıkarmak

## Faz 1 - Core Platform

- Ortak backend çekirdeği
- Auth sistemi
- RBAC
- Audit log altyapısı
- Logging ve error handling
- PostgreSQL ve migration altyapısı
- Redis ve RabbitMQ bağlantı yapısı
- API gateway / BFF yaklaşımının netleştirilmesi

## Faz 2 - Customer Identity and Accounts

- Kayıt
- Giriş
- Refresh token
- Profil yönetimi
- Yolcu bilgileri
- Fatura bilgileri
- Kullanıcı paneli temel ekranları

## Faz 3 - Search and Offer Foundation

- Flight provider entegrasyon katmanı
- Hotel provider entegrasyon katmanı
- Car rental provider entegrasyon katmanı
- Arama normalizasyon modeli
- Fiyatlama ve kur dönüştürme altyapısı
- Arama önbellekleme yaklaşımı

## Faz 4 - Booking and Payment

- Sepet
- Checkout
- iyzico entegrasyonu
- Ön rezervasyon / hold akışları
- Booking oluşturma
- İptal / değişiklik temel akışları

## Faz 5 - Admin and Operations

- Admin auth
- Tedarikçi / sağlayıcı izleme ekranları
- Booking operasyon ekranları
- Kampanya yönetimi için temel yapı
- İç raporlama temeli

## Faz 6 - Package and Cross Sell

- Dinamik paket mantığı
- Flight + Hotel
- Flight + Hotel + Car
- Bundle fiyatlama kuralları

## Faz 7 - Scale and Expansion

- Çoklu dil
- Çoklu para birimi
- Çoklu ülke
- Affiliate
- B2B
- Supplier portal
- Mobile app production release

## Kritik Not

İlk aşamada IATA alınmayacağı için uçak biletleme ve benzeri lisanslı süreçler partner ve consolidator katmanı üzerinden çözülecek. Bu karar veri modeli, muhasebe akışı, refund süreci ve operasyon ekranlarını doğrudan etkiler.
