# Google VM Deployment Plan

## Mevcut Aşama

Tek VM üzerinde ilk production-benzeri kurulum planı

## Mimari Karar

İlk yayın için en doğru yaklaşım:

- 1 adet Ubuntu VM
- Docker Engine
- Docker Compose
- Nginx reverse proxy
- Aynı VM üzerinde PostgreSQL, Redis, RabbitMQ, MinIO ve backend servisleri

Bu yapı kalıcı son mimari değildir, ama MVP için profesyonel ve kontrollü bir başlangıçtır.

## Neden doğru seçim

- Lokal makine kısıtını aşar
- Tekrarlanabilir kurulum sağlar
- GitHub tabanlı deploy akışına izin verir
- İleride managed database veya Kubernetes'e geçişi engellemez

## Önerilen İlk Sunucu Özellikleri

- Ubuntu 24.04 LTS
- En az 2 vCPU
- En az 4 GB RAM
- En az 40 GB SSD

Not:

Uçak, otel, araç sağlayıcı entegrasyonları ve admin paneli eklendikçe 8 GB RAM çok daha güvenli olur.

## Ağ ve Güvenlik

İlk açılması gereken portlar:

- `22` SSH
- `80` HTTP

Henüz açılmaması gerekenler:

- `5432` PostgreSQL
- `6379` Redis
- `5672` RabbitMQ
- `9000` MinIO API

Bu servisler dış dünyaya açılmamalı.

## Repo ve Secret Kuralı

`.env` dosyası repoya commit edilmemeli.

Doğru yaklaşım:

- repo içinde sadece `.env.example`
- gerçek `.env` sadece sunucuda

Eğer `.env` GitHub'a push edildi ise, secret'ları değiştirmemiz gerekir.

## Kurulum Akışı

1. Ubuntu VM oluştur
2. VM'ye SSH ile bağlan
3. `infra/scripts/bootstrap-ubuntu.sh` çalıştır
4. Repo'yu `/opt/tsa` altına clone et
5. Sunucuda gerçek `.env` oluştur
6. `infra/scripts/deploy-vm.sh` çalıştır

## Operasyon Notu

Deploy script'i rebuild sonrasında `nginx` servisini yeniden başlatır ve temel healthcheck çağrısı yapar.

Bu, container IP değişiminden kaynaklanabilen geçici `502 Bad Gateway` durumlarını azaltır.

## Sonraki Güçlendirme Adımları

1. Domain bağlama
2. HTTPS
3. Otomatik deploy
4. Managed PostgreSQL değerlendirmesi
5. Yedekleme stratejisi
