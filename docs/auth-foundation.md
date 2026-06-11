# Auth Foundation

## Bu fazda eklenenler

- kullanıcı tablosu
- rol tablosu
- yetki tablosu
- kullanıcı rol eşlemesi
- rol yetki eşlemesi
- kullanıcı session tablosu
- register endpoint
- login endpoint
- refresh endpoint
- logout endpoint
- bearer token ile `/users/me`

## Endpointler

- `POST /api/auth/register`
- `POST /api/auth/login`
- `POST /api/auth/refresh`
- `POST /api/auth/logout`
- `GET /api/users/me`

## İlk migration

Migration dosyası:

- `services/user-service/migrations/0001_init_auth_foundation.sql`

Sunucuda uygulamak için:

```bash
cd /opt/tsa
chmod +x services/user-service/scripts/apply_migration.sh
source .env
./services/user-service/scripts/apply_migration.sh
```

## Şu an bilinçli olarak henüz yapılmayanlar

- email verification
- password reset
- route bazlı tam permission enforcement

Bunları sonraki auth sprintinde ekleyeceğiz.
