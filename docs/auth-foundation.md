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
- password reset request endpoint
- password reset confirm endpoint
- bearer token ile `/users/me`
- admin access kontrol endpointi
- auth audit log kayitlari

## Endpointler

- `POST /api/auth/register`
- `POST /api/auth/login`
- `POST /api/auth/refresh`
- `POST /api/auth/logout`
- `POST /api/auth/password-reset/request`
- `POST /api/auth/password-reset/confirm`
- `GET /api/users/me`
- `GET /api/admin/access-check`

## İlk migration

Migration dosyalari:

- `services/user-service/migrations/0001_init_auth_foundation.sql`
- `services/user-service/migrations/0002_admin_audit_password_reset.sql`

Sunucuda uygulamak için:

```bash
cd /opt/tsa
chmod +x services/user-service/scripts/apply_migration.sh
source .env
./services/user-service/scripts/apply_migration.sh
```

Ilk admin rolunu vermek icin:

```bash
cd /opt/tsa
source .env
chmod +x services/user-service/scripts/grant_admin_role.sh
./services/user-service/scripts/grant_admin_role.sh test@example.com
```

## Şu an bilinçli olarak henüz yapılmayanlar

- email verification
- reset token e-posta teslimati
- route bazlı genis permission enforcement
- email verification

Bunları sonraki auth sprintinde ekleyeceğiz.
