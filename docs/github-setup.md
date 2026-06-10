# GitHub Setup

## Hedef

Yerel geliştirme kısıtlı olduğu için bu repo GitHub merkezli ilerleyecek ve çalışma ortamı Google VM olacak.

## Önemli Uyarı

`.env` dosyası repoya eklenmemeli.

Eğer sadece kendi bilgisayarındaki proje klasörüne koyduysan sorun yok.
Ama GitHub repo içine commit ettiysen:

1. Secret değerlerini değiştir
2. `.env` dosyasını repodan çıkar
3. Gerçek secret'ları sadece sunucuda tut

## Bizim Çalışma Modelimiz

- Kod bu repoda ilerleyecek
- Deploy Google VM'ye yapılacak
- Docker sadece sunucuda çalışacak

## Sonraki Git Adımı

Bu klasörde git başlatılıp GitHub repona bağlanmalı, ardından ilk commit ve ilk push yapılmalı.
