# SMS Panel

Laravel 12 tabanlı SMS yönetim paneli. AdminLTE 3 arayüzü, rol/yetki sistemi (Spatie Permission), cüzdan, paket satışı, destek ticket, rehber, kampanya ve REST API içerir.

## Gereksinimler

- PHP 8.2+
- Composer 2.x
- MySQL 8+ veya MariaDB 10.6+ (production önerilir)
- PHP eklentileri: `openssl`, `pdo`, `mbstring`, `tokenizer`, `xml`, `ctype`, `json`, `bcmath`, `fileinfo`

## Hızlı kurulum (geliştirme)

```bash
git clone https://github.com/KULLANICI/smspanel.git
cd smspanel

composer install
cp .env.example .env
php artisan key:generate

# .env içinde veritabanını ayarlayın (MySQL örneği):
# DB_CONNECTION=mysql
# DB_HOST=127.0.0.1
# DB_DATABASE=smspanel
# DB_USERNAME=root
# DB_PASSWORD=

php artisan migrate --seed
php artisan storage:link

php artisan serve
```

Panel: `http://127.0.0.1:8000/giris`

SMS/kampanya kuyruğu için ayrı terminal:

```bash
php artisan queue:work --queue=sms --tries=3
```

## Varsayılan hesaplar (seed sonrası)

| Rol | E-posta | Şifre |
|-----|---------|-------|
| Süper Yönetici | admin@allwhite.com.tr | Allwhite123! |
| Süper Yönetici (dev) | admin@smspanel.local | Admin123! |
| Müşteri | musteri@smspanel.local | Musteri123! |

Production ortamında seed sonrası bu şifreleri mutlaka değiştirin.

## Modüller

- SMS gönderim (tekil / toplu, max 100/istek)
- SMS geçmişi ve CSV dışa aktarma
- Rehber (CSV içe/dışa aktarma)
- SMS kampanyaları (200k alıcı, kuyruk ile parça gönderim)
- SMS şablonları
- Cüzdan ve SMS paketleri
- Destek ticket sistemi
- Organizasyon ve kullanıcı yönetimi (süper yönetici)
- REST API (`/api/v1/...`, Bearer token)

## API

Token: Panel → Ayarlar → API Token oluştur.

```bash
curl -H "Authorization: Bearer TOKEN" https://alanadiniz.com/api/v1/balance
```

## Testler

```bash
php artisan test
```

## Plesk kurulumu

Detaylı adımlar: [docs/DEPLOY-PLESK.md](docs/DEPLOY-PLESK.md)

## GitHub'a ilk yükleme

Bu makinede Git yüklü değilse [Git for Windows](https://git-scm.com/download/win) kurun, ardından proje klasöründe:

```bash
cd d:\laragon\www\smspanel
vv
git init
git add .
git commit -m "Initial commit: SMS Panel Laravel 12"

# GitHub'da boş repo oluşturun (README eklemeyin), sonra:
git branch -M main
git remote add origin https://github.com/KULLANICI/smspanel.git
git push -u origin main
```

GitHub CLI kullanıyorsanız:

```bash
gh repo create smspanel --private --source=. --remote=origin --push
```

## Ortam değişkenleri

| Değişken | Açıklama |
|----------|----------|
| `SMS_DEFAULT_PROVIDER` | `mock`, `netgsm`, `iletimerkezi` |
| `SMS_QUEUE` | Kuyruk adı (varsayılan: `sms`) |
| `SMS_CAMPAIGN_MAX_RECIPIENTS` | Kampanya max alıcı (200000) |
| `QUEUE_CONNECTION` | `database` (Plesk'te önerilir) |

## Lisans

MIT
