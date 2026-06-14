# ADR 0001 - Platform Foundation

## Durum

Kabul edildi

## Tarih

2026-06-10

## Karar

Travel Super App başlangıç aşamasında aşağıdaki prensiplerle geliştirilecektir:

1. Monorepo kullanılacak.
2. Backend tarafında FastAPI tercih edilecek.
3. Frontend ve admin için Next.js kullanılacak.
4. Mobile için Flutter kullanılacak.
5. Ana veritabanı PostgreSQL olacak.
6. Cache için Redis kullanılacak.
7. Queue için RabbitMQ kullanılacak.
8. İlk aşamada modüler monolith + service boundaries modeli uygulanacak.
9. Uçak, otel ve araç sağlayıcı entegrasyonları adapter yapısı ile soyutlanacak.
10. Ödeme sağlayıcısı olarak ilk aşamada iyzico kullanılacak.

## Gerekçe

- MVP'ye hızlı çıkmak
- Erken aşamada gereksiz dağıtık sistem karmaşıklığından kaçınmak
- Orta ve uzun vadede ayrıştırılabilir bir yapı kurmak
- Teknik kararları baştan tutarlı hale getirmek

## Sonuçlar

### Pozitif

- Daha yönetilebilir geliştirme süreci
- Daha düşük operasyonel karmaşıklık
- Daha güçlü domain sınırları

### Negatif

- Baştan tam bağımsız deploy edilen servis yapısı olmayacak
- Ayrıştırma için sonraki fazlarda planlı refactor gerekecek

## Uygulama Notu

Bu karar, erken aşamada hız ve kalite dengesini korumak için alınmıştır. Trafik, ekip büyüklüğü ve servis bağımsızlığı gereksinimi arttığında belirlenen domain sınırları doğrultusunda ayrıştırma yapılacaktır.
