# Koperasi-SipaduHok

Toko online koperasi sekolah untuk buku, alat tulis, dan atribut sekolah. Project ini berdiri sendiri dari aplikasi SPP Sipaduhok: repository, database, session, akun, order, dan kredensial payment gateway tidak dibagi dengan `app.sipaduhok.id`.

Target produksi: `https://koperasi.sipaduhok.id`.

## Stack

- Laravel 13 / PHP 8.3
- Blade + Tailwind CSS 4 + Alpine.js + Vite
- MySQL untuk development dan produksi; SQLite in-memory hanya untuk test otomatis
- Midtrans Snap tersedia melalui adapter `PaymentGateway`; mode `placeholder` tetap tersedia untuk development
- Tidak memakai RajaOngkir, Komerce, ekspedisi, atau API pengiriman lain

## Role akun

- `admin`: seller/pengelola Koperasi Sipaduhok
- `pembeli`: akses dashboard, katalog, wishlist, alamat tersimpan, cart, checkout, riwayat, invoice, ulasan, dan konfirmasi penerimaan

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

## Pengalaman retail

- Profil, pengaturan keamanan, reset kata sandi, dropdown akun, dan notifikasi database untuk kedua role
- Galeri maksimal lima foto dengan preview sebelum upload dan lightbox pada detail produk/bukti tiba
- Wishlist dan alamat tersimpan khusus pembeli
- Ulasan hanya untuk pembelian yang selesai; admin dapat memberi balasan resmi
- Filter katalog berdasarkan kategori, harga, rating, dan urutan
- Label pengiriman cetak untuk Kurir Koperasi
- Halaman tentang, bantuan, pembayaran, pengiriman, pengembalian, privasi, dan syarat ketentuan
- Identitas/kontak publik koperasi dapat diatur melalui panel admin

Hasil audit serta keputusan adaptasi fitur dari DigiRack dicatat di [`docs/digirack-feature-reconciliation.md`](docs/digirack-feature-reconciliation.md).

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

Mode bawaan development adalah `placeholder`: checkout membuat referensi `DEMO-*`, lalu admin menekan **Konfirmasi Pembayaran Internal** agar stok berkurang dan order mulai diproses.

Untuk mengaktifkan Midtrans Sandbox:

```dotenv
PAYMENT_GATEWAY=midtrans
MIDTRANS_SERVER_KEY=SB-Mid-server-...
MIDTRANS_CLIENT_KEY=SB-Mid-client-...
MIDTRANS_IS_PRODUCTION=false
```

Atur Payment Notification URL di dashboard Midtrans ke:

```text
https://koperasi.sipaduhok.id/payments/midtrans/notification
```

Setiap order Koperasi menjadi satu transaksi Midtrans. Callback diverifikasi memakai signature SHA-512, nominal callback dicocokkan dengan total order, dan pengurangan stok bersifat idempoten sehingga callback berulang tidak mengurangi stok dua kali. Redirect browser hanya mengubah tampilan; status lunas tetap berasal dari callback server-to-server atau sinkronisasi status ke Midtrans.

Website dan pengajuan merchant harus menampilkan identitas badan usaha/perorangan, hubungan kemitraan, katalog, harga, proses pemesanan, kebijakan, dan barang yang benar-benar dijalankan. Pemisahan aplikasi tidak boleh dipakai untuk menyamarkan entitas atau menghindari persyaratan onboarding payment gateway.

## Verifikasi

```powershell
php artisan migrate:fresh --seed
php artisan test
vendor\bin\pint --test
npm run build
```

Test mencakup registrasi, profil/keamanan, reset kata sandi, notifikasi, wishlist, alamat checkout, galeri/preview produk, import Excel beserta template contoh, format input rupiah, ulasan terverifikasi, halaman legal, identitas koperasi, Beli Langsung, checkout item terpilih, satu kurir, callback Midtrans tervalidasi dan idempoten, workflow pengiriman, bukti tiba, invoice, serta otorisasi lintas akun.

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
