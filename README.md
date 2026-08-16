# Koperasi Sipaduhok

Toko online koperasi sekolah untuk buku, alat tulis, dan atribut sekolah. Project ini berdiri sendiri dari aplikasi SPP Sipaduhok: repository, database, session, akun, order, dan kredensial payment gateway tidak dibagi dengan `app.sipaduhok.id`.

Target produksi: `https://koperasi.sipaduhok.id`.

## Stack

- Laravel 13 / PHP 8.3
- Blade + Tailwind CSS 4 + Vite
- MySQL untuk development dan produksi; SQLite in-memory hanya untuk test otomatis
- Payment gateway masih `placeholder` dan sudah dipisahkan melalui kontrak `PaymentGateway`
- Tidak memakai RajaOngkir, Komerce, ekspedisi, atau API pengiriman lain

## Role dan tipe akun

- `admin`: seller/pengelola Koperasi Sipaduhok
- `pembeli`: akses katalog, cart, checkout, riwayat, invoice, dan konfirmasi penerimaan
- Pembeli memiliki tipe profil `siswa` atau `orang_tua`; tipe ini bukan role tambahan

Registrasi publik selalu membuat akun `pembeli`. Akun admin dibuat melalui seeder/environment.

## Pengiriman

Sistem hanya memiliki satu Kurir Koperasi. Admin mengatur nama, tarif flat, estimasi, serta status aktifnya. Checkout otomatis menggunakan kurir tersebut dan menyimpan nama/tarif sebagai snapshot pada order.

Alur status:

```text
menunggu pembayaran
  -> diproses
  -> siap dikirim
  -> dalam pengantaran
  -> tiba di alamat (admin wajib unggah foto bukti)
  -> selesai (pembeli konfirmasi penerimaan)
```

Semua perpindahan status dicatat dalam `order_status_histories`.

## Instalasi development (PowerShell)

Project ini sudah dibuat di `C:\laragon\www\koperasi-sipaduhok`. Untuk instalasi ulang dari hasil repository:

```powershell
cd C:\laragon\www\koperasi-sipaduhok
composer install
Copy-Item .env.example .env
php artisan key:generate
php artisan migrate --seed
npm install
php artisan storage:link
npm run build
composer run dev
```

Pada Laragon, reload web server bila virtual host belum terdeteksi, lalu buka `http://koperasi-sipaduhok.test`.

## Akun development

Hanya tersedia saat `APP_ENV=local` atau `testing`:

- Admin: `admin@koperasi.test` / `password`
- Pembeli: `pembeli@koperasi.test` / `password`

Untuk produksi, isi `ADMIN_EMAIL` dan `ADMIN_PASSWORD` di `.env` sebelum menjalankan seeder. Jangan gunakan kredensial development di server publik.

## Payment gateway

Checkout saat ini membuat referensi `DEMO-*`. Admin menekan **Konfirmasi Pembayaran** agar stok berkurang dan order masuk tahap diproses.

Implementasi Midtrans/Tripay berikutnya cukup membuat adapter baru untuk `App\Contracts\PaymentGateway`, mengganti binding pada `AppServiceProvider`, dan menambahkan webhook bertanda tangan. Status pembayaran tidak boleh ditentukan dari redirect browser tanpa verifikasi server-to-server/webhook.

Website dan pengajuan merchant harus menampilkan identitas badan usaha/perorangan, hubungan kemitraan, katalog, harga, proses pemesanan, kebijakan, dan barang yang benar-benar dijalankan. Pemisahan aplikasi tidak boleh dipakai untuk menyamarkan entitas atau menghindari persyaratan onboarding payment gateway.

## Verifikasi

```powershell
php artisan migrate:fresh --seed
php artisan test
vendor\bin\pint --test
npm run build
```

Test mencakup registrasi dua tipe pembeli, pemisahan role, checkout satu kurir, snapshot ongkir, pengurangan stok saat pembayaran dikonfirmasi, workflow pengiriman, upload bukti oleh admin, konfirmasi pembeli, invoice, dan otorisasi invoice.

## Struktur fitur utama

```text
app/
  Contracts/PaymentGateway.php
  Enums/
  Http/Controllers/
    Admin/
  Http/Middleware/EnsureRole.php
  Models/
  Services/
resources/views/
  admin/
  auth/
  cart/
  catalog/
  checkout/
  orders/
database/
  migrations/
  seeders/
tests/Feature/
```

Untuk produksi, arahkan document root subdomain ke folder `public`, bukan root repository.
