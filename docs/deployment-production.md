# Deployment production Toko Sipaduhok

## Web server dan HTTPS

- Domain: `https://toko.sipaduhok.id`
- Document root harus mengarah ke `<project>/public`, bukan root repository.
- Pakai sertifikat HTTPS valid. Jika memakai Cloudflare, gunakan mode SSL/TLS **Full (strict)** dan batasi akses origin bila memungkinkan.
- HTTP harus dialihkan permanen ke HTTPS.
- Webhook Paywuz: `https://toko.sipaduhok.id/payments/paywuz/webhook`.

Konfigurasi minimum `.env` production:

```dotenv
APP_NAME="Toko Sipaduhok"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://toko.sipaduhok.id

SESSION_DRIVER=database
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax
SESSION_DOMAIN=toko.sipaduhok.id

FILESYSTEM_DISK=local
PUBLIC_STORAGE_URL=/storage
QUEUE_CONNECTION=database
CACHE_STORE=database

PAYMENT_GATEWAY=paywuz
PAYWUZ_ENVIRONMENT=production
PAYWUZ_BASE_URL=https://api.paywuz.id/v1
PAYWUZ_PRODUCTION_API_KEY=pk_live_...

TURNSTILE_HOSTNAME=toko.sipaduhok.id
TURNSTILE_SITE_KEY=...
TURNSTILE_SECRET_KEY=...

LOG_LEVEL=warning
```

Jangan mengganti `APP_KEY` pada aplikasi yang sudah menyimpan kredensial Paywuz terenkripsi. Simpan backup `.env` secara privat.

## Perintah deployment

Jalankan dari root project:

```bash
composer install --no-dev --optimize-autoloader --no-interaction
npm ci
npm run build
php artisan migrate --force
php artisan storage:link
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Migrasi wilayah pertama kali memasukkan sekitar 91 ribu baris data wilayah
lokal. Jangan menghentikan proses saat migrasi ini berjalan. Form alamat tidak
memakai RajaOngkir atau API wilayah saat runtime; akses keluar yang digunakan
di halaman alamat hanya tile peta OpenStreetMap melalui HTTPS.

Pastikan user PHP-FPM/web server dapat menulis ke `storage` dan `bootstrap/cache`, serta symlink `public/storage` mengarah ke `storage/app/public`.

Worker queue production:

```bash
php artisan queue:work --sleep=3 --tries=3 --max-time=3600
```

Kelola worker dengan Supervisor/systemd dan restart worker setelah deployment:

```bash
php artisan queue:restart
```

## Pemeriksaan setelah deployment

```bash
curl -I http://toko.sipaduhok.id
curl -I https://toko.sipaduhok.id
curl -I https://toko.sipaduhok.id/up
```

Hasil yang diharapkan:

- HTTP mengarah ke HTTPS dengan status `301` atau `308`.
- Halaman HTTPS dan `/up` mengembalikan `200`.
- Cookie session memiliki atribut `Secure`, `HttpOnly`, dan `SameSite=Lax`.
- URL CSS, JavaScript, gambar, email, dan webhook menggunakan `https://`.
- Login, Turnstile, upload gambar, checkout Sandbox/Production, webhook, dan email OTP diuji setelah deployment.
- Form alamat dapat memuat provinsi hingga desa/kelurahan, peta tampil, dan URL navigasi dapat disalin admin.
