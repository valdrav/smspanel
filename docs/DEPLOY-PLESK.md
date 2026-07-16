# Plesk'e Kurulum Rehberi

Bu rehber, projeyi GitHub'dan çekip Plesk üzerinde production ortamına almak içindir.

## 1. GitHub'dan projeyi alın

Plesk → **Websites & Domains** → siteniz → **Git** (veya SSH ile):

```bash
cd /var/www/vhosts/alanadiniz.com
git clone https://github.com/KULLANICI/smspanel.git httpdocs
cd httpdocs
```

Alternatif: Plesk Git extension ile repo URL'sini bağlayıp otomatik deploy kullanın.

## 2. Document root ayarı

Laravel için kök dizin **`public`** olmalı.

Plesk → **Hosting Settings** → **Document root**:

```
/httpdocs/public
```

## 3. PHP sürümü

**PHP 8.2 veya 8.3** seçin. Gerekli eklentiler açık olsun:

`openssl`, `pdo_mysql`, `mbstring`, `tokenizer`, `xml`, `ctype`, `json`, `bcmath`, `fileinfo`, `curl`

## 4. Veritabanı

Plesk → **Databases** → MySQL veritabanı ve kullanıcı oluşturun.

SSH veya Plesk terminal:

```bash
cd /var/www/vhosts/alanadiniz.com/httpdocs

composer install --no-dev --optimize-autoloader

cp .env.example .env
php artisan key:generate
```

`.env` production ayarları (örnek):

```env
APP_NAME="SMS Panel"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://sms.inovapp.tr

APP_LOCALE=tr
APP_FALLBACK_LOCALE=tr

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=smspanel_db
DB_USERNAME=smspanel_user
DB_PASSWORD=****

SESSION_DRIVER=database
QUEUE_CONNECTION=database
CACHE_STORE=database

SMS_DEFAULT_PROVIDER=easysendsms
SMS_QUEUE=sms
SMS_BATCH_SIZE=1000
```

API anahtarı ve onaylı gönderici başlığı production `.env` dosyasına yazılmak
zorunda değildir. Süper Yönetici → **SMS API Ayarları** ekranından EasySendSMS
kaydını düzenleyin; anahtarı ve sender ID'yi girip **Aktif** ve **Varsayılan**
seçeneklerini işaretleyin. Ayarlar veritabanında şifreli saklanır. Aynı ekrandaki
bakiye sorgusu SMS göndermez.

Domain: **sms.inovapp.tr** — Document root: `.../httpdocs/public`

```bash
php artisan migrate --force --seed
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Seed sonrası admin şifresini değiştirin.

## 5. Klasör izinleri

```bash
chown -R www-data:psacln storage bootstrap/cache
chmod -R 775 storage bootstrap/cache
```

Plesk'te PHP-FPM kullanıcısı farklı olabilir; Plesk File Manager'dan `storage` ve `bootstrap/cache` yazılabilir olmalı.

## 6. SMS kuyruk worker (zorunlu)

Kampanya ve toplu SMS için sürekli çalışan worker gerekir.

### Seçenek A: Supervisor (önerilir)

Sunucuda Supervisor varsa `/etc/supervisor/conf.d/smspanel-worker.conf`:

```ini
[program:smspanel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/vhosts/alanadiniz.com/httpdocs/artisan queue:work database --queue=sms --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/vhosts/alanadiniz.com/logs/sms-worker.log
stopwaitsecs=3600
```

```bash
supervisorctl reread
supervisorctl update
supervisorctl start smspanel-worker:*
```

### Seçenek B: Plesk Scheduled Tasks (cron)

Her dakika (geçici çözüm, yoğun trafikte yetersiz kalabilir):

```
* * * * * cd /var/www/vhosts/alanadiniz.com/httpdocs && php artisan queue:work database --queue=sms --stop-when-empty >> /dev/null 2>&1
```

Laravel scheduler için ayrıca:

```
* * * * * cd /var/www/vhosts/alanadiniz.com/httpdocs && php artisan schedule:run >> /dev/null 2>&1
```

## 7. HTTPS

Plesk → **SSL/TLS Certificates** → Let's Encrypt ile sertifika alın.

`.env` içinde `APP_URL=https://alanadiniz.com` olduğundan emin olun.

## 8. Deploy sonrası kontrol listesi

- [ ] `/giris` sayfası açılıyor
- [ ] Giriş yapılabiliyor
- [ ] Tekil SMS kuyruğa düşüyor
- [ ] Worker loglarında hata yok
- [ ] `storage/app` altına destek ekleri yazılabiliyor
- [ ] API token ile `/api/v1/balance` yanıt veriyor

## 9. Güncelleme (deploy)

```bash
cd /var/www/vhosts/alanadiniz.com/httpdocs
git pull origin main
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
supervisorctl restart smspanel-worker:*
```

## Sorun giderme

| Sorun | Çözüm |
|-------|-------|
| 500 hatası | `storage/logs/laravel.log` kontrol edin, izinleri düzeltin |
| SMS gitmiyor | Worker çalışıyor mu? `jobs` tablosunu kontrol edin |
| CSS/JS yok | Document root `public` mi? |
| Oturum düşüyor | `SESSION_DRIVER=database`, migrate yapıldı mı? |
