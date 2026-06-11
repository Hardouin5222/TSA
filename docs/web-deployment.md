# Web Deployment

## Amaç

Next.js B2C web uygulamasini ayni VM ve ayni domain altinda yayinlamak.

## Routing Mantigi

- `/api/*` -> `api-gateway`
- diger tum yollar -> `web`

Bu sayede:

- kullanici tek domain gorur
- frontend tarafinda CORS karmasasi azalir
- auth ve arama akislarini ayni host uzerinden baglamak kolaylasir

## Bu fazda eklenenler

- `apps/web/Dockerfile`
- `web` service tanimi
- Nginx reverse proxy ayrimi
- frontend icin relative API base URL destegi

## Sonraki adim

Web container deploy edildikten sonra:

1. landing ekranini tarayicidan ac
2. `/login` ve `/register` ekranlarini kontrol et
3. auth formunu canli backend ile test et
4. ardindan arama formunu gercek backend kontratina yaklastir
