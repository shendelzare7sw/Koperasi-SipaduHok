# Toko Sipaduhok

Toko online mandiri untuk buku, alat tulis, atribut sekolah, dan kebutuhan belajar. Seluruh katalog, akun, pesanan, pembayaran, invoice, dan pengiriman dikelola langsung oleh Toko Sipaduhok.

Target produksi: `https://toko.sipaduhok.id`.

## Stack

- Laravel 13 / PHP 8.3
- Blade + Tailwind CSS 4 + Alpine.js + Vite
- MySQL untuk development dan produksi; SQLite in-memory hanya untuk test otomatis
- Paywuz REST API tersedia melalui adapter `PaymentGateway`; mode `placeholder` tetap tersedia untuk development
- Tidak memakai RajaOngkir, Komerce, ekspedisi, atau API pengiriman lain

## Role akun

- `admin`: seller/pengelola Toko Sipaduhok
- `pembeli`: akses dashboard, katalog, wishlist, alamat tersimpan, cart, checkout, riwayat, invoice, ulasan, dan konfirmasi penerimaan

Registrasi publik selalu membuat akun `pembeli`, tetapi data user baru disimpan setelah kode OTP email berhasil diverifikasi. Akun admin dibuat melalui seeder/environment.

Sebelum checkout, pembeli wajib mengirim foto KTP dan menunggu persetujuan admin. Dokumen disimpan pada disk privat (`storage/app/private`), NIK disimpan terenkripsi, dan route dokumen hanya dapat dibuka oleh pemilik atau admin. Admin dapat menonaktifkan akun pembeli; tindakan ini mencabut seluruh sesi aktif dan mencegah login berikutnya.

## Keamanan akun

- Login, registrasi, dan formulir pemulihan akun dilindungi Cloudflare Turnstile dengan validasi wajib di server.
- Hasil Turnstile juga diperiksa terhadap `action` formulir dan hostname production untuk mencegah token dari halaman/domain lain digunakan kembali.
- Login dibatasi per kombinasi email dan alamat IP; endpoint OTP juga memiliki route rate limit.
- OTP berisi enam digit, berlaku 10 menit, maksimal lima percobaan, maksimal tiga kali kirim ulang, dan memiliki jeda kirim ulang 60 detik.
- Registrasi tidak membuat user sebelum OTP benar. Pemulihan akun menerima email atau nomor HP, mengirim OTP ke email terdaftar, lalu memberi sesi singkat untuk mengganti kata sandi.
- Respons awal pemulihan dibuat generik agar tidak membocorkan apakah email atau nomor HP terdaftar.
- Session database dienkripsi pada konfigurasi contoh dan seluruh session login lama dicabut setelah kata sandi dipulihkan.

Untuk production, buat widget Turnstile di Cloudflare dengan hostname `toko.sipaduhok.id`, kemudian isi tanpa tanda kutip tambahan:

```dotenv
TURNSTILE_SITE_KEY=site-key-dari-cloudflare
TURNSTILE_SECRET_KEY=secret-key-dari-cloudflare
TURNSTILE_HOSTNAME=toko.sipaduhok.id
SESSION_ENCRYPT=true
```

Jika kedua key Turnstile kosong, pemeriksaan dilewati agar development lokal tetap dapat dijalankan. Jika hanya salah satu key terisi, autentikasi akan gagal tertutup sampai konfigurasi diperbaiki. Setelah mengubah `.env` di server, jalankan `php artisan optimize:clear` lalu `php artisan config:cache`.

OTP memerlukan email sungguhan di production. Ubah `MAIL_MAILER=log` ke SMTP/provider transaksi yang digunakan dan isi `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, serta `MAIL_FROM_ADDRESS`. Jangan commit secret Turnstile, SMTP, atau Paywuz.

## Pengiriman

Sistem hanya memiliki satu Kurir Toko. Admin mengatur nama, tarif flat, estimasi, serta status aktifnya. Checkout otomatis menggunakan kurir tersebut dan menyimpan nama/tarif sebagai snapshot pada order.

Alur status:

```text
menunggu pembayaran
  -> diproses
  -> siap dikirim (penanda tanpa lampiran)
  -> dalam pengantaran (admin wajib unggah 1–5 foto)
  -> tiba di alamat (admin wajib unggah 1–5 foto)
  -> selesai (pembeli konfirmasi penerimaan)
```

Semua perpindahan status dicatat dalam `order_status_histories`.

## Pengalaman retail

- Profil, pengaturan keamanan, pemulihan akun berbasis OTP, dropdown akun/notifikasi, serta dropdown keranjang desktop
- Galeri maksimal lima foto dengan preview sebelum upload dan lightbox pada detail produk/bukti tiba
- Wishlist dan CRUD alamat lengkap khusus pembeli; checkout wajib memilih alamat tersimpan dan menyimpan snapshot alamat pada pesanan/invoice
- Ulasan hanya untuk pembelian yang selesai; admin dapat memberi balasan resmi
- Filter katalog berdasarkan kategori, harga, rating, dan urutan
- Import produk massal melalui template Excel dengan contoh, validasi per baris, dan dukungan kategori tambahan `Lainnya`
- Label pengiriman cetak untuk Kurir Toko
- Halaman tentang, bantuan, pembayaran, pengiriman, pengembalian, privasi, dan syarat ketentuan
- Identitas/kontak publik toko dapat diatur melalui panel admin
- Arsip produk dengan pemulihan dan hapus permanen; hapus permanen sekaligus membersihkan semua file galeri produk
- Kelola pembeli dengan filter status akun/KTP, pemeriksaan KTP, riwayat pesanan, serta aktif/nonaktif akun
- Panel konfigurasi Paywuz dengan key Sandbox/Production terenkripsi dan konfirmasi kata sandi admin
- Landing katalog ringkas dengan pencarian dan shortcut ikon kategori; halaman hasil memakai sortir terpisah, sidebar filter desktop, serta drawer filter kiri pada mobile

Hasil audit serta keputusan adaptasi fitur dari DigiRack dicatat di [`docs/digirack-feature-reconciliation.md`](docs/digirack-feature-reconciliation.md).

## Instalasi development (PowerShell)

Untuk instalasi lokal di Laragon, gunakan direktori `C:\laragon\www\toko-sipaduhok`:

```powershell
cd C:\laragon\www\toko-sipaduhok
composer install
Copy-Item .env.example .env
php artisan key:generate
php artisan migrate --seed
npm install
php artisan storage:link
npm run build
composer run dev
```

Pada Laragon, reload web server bila virtual host belum terdeteksi, lalu buka `http://toko-sipaduhok.test`.

## Akun development

Hanya tersedia saat `APP_ENV=local` atau `testing`:

- Admin: `admin@toko.test` / `password`
- Pembeli: `pembeli@toko.test` / `password`

Untuk produksi, isi `ADMIN_EMAIL` dan `ADMIN_PASSWORD` di `.env` sebelum menjalankan seeder. Jangan gunakan kredensial development di server publik.

## Payment gateway

Mode bawaan development adalah `placeholder`: checkout membuat referensi `DEMO-*`, lalu admin menekan **Konfirmasi Pembayaran Internal** agar stok berkurang dan order mulai diproses.

Untuk mengaktifkan Paywuz Sandbox melalui `.env`:

```dotenv
PAYMENT_GATEWAY=paywuz
PAYWUZ_SANDBOX_API_KEY=pk_sand_...
PAYWUZ_PRODUCTION_API_KEY=pk_live_...
PAYWUZ_ENVIRONMENT=sandbox
PAYWUZ_BASE_URL=https://api.paywuz.id/v1
PAYWUZ_EXPIRY_MINUTES=720
```

Admin juga dapat membuka **Admin → Pembayaran** untuk mengatur konfigurasi yang sama melalui UI. Nilai panel menjadi override `.env`; API key Sandbox dan Production dienkripsi memakai `APP_KEY`, tidak ditampilkan kembali, dan perubahan memerlukan kata sandi admin. Biarkan kolom key kosong bila key tersimpan tidak ingin diganti.

Mode `placeholder` hanya dapat checkout pada environment `local` atau `testing`. Pada production, checkout terkunci sampai konfigurasi Paywuz lengkap dan diaktifkan.

Atur Webhook URL pada proyek Sandbox dan Production di dashboard Paywuz ke:

```text
https://toko.sipaduhok.id/payments/paywuz/webhook
```

Setiap order toko menjadi satu transaksi Paywuz dengan `orderId` invoice yang idempoten. Webhook diverifikasi dari raw body memakai `X-Paywuz-Signature` HMAC-SHA256, `X-Paywuz-Delivery` dideduplikasi, serta referensi dan nominal dicocokkan. Event `transaction.settlement` menandakan pembayaran pelanggan telah dikonfirmasi sehingga pesanan langsung diproses dan stok berkurang tepat satu kali; event `transaction.paid` berikutnya menandakan dana telah masuk ke saldo merchant dan tetap diproses secara idempoten.

Website dan pengajuan merchant harus menampilkan identitas badan usaha/perorangan, hubungan kemitraan, katalog, harga, proses pemesanan, kebijakan, dan barang yang benar-benar dijalankan. Pemisahan aplikasi tidak boleh dipakai untuk menyamarkan entitas atau menghindari persyaratan onboarding payment gateway.

## Verifikasi

```powershell
php artisan migrate:fresh --seed
php artisan test
vendor\bin\pint --test
npm run build
```

Test mencakup registrasi OTP, pemulihan akun OTP, validasi server-side Turnstile, profil/keamanan, notifikasi, wishlist, alamat checkout, verifikasi KTP privat, aktivasi/nonaktivasi pembeli, arsip/pulihkan/hapus permanen produk, panel Paywuz terenkripsi, galeri/preview produk, import Excel beserta template contoh, format input rupiah, ulasan terverifikasi, halaman legal, identitas toko, Beli Langsung, checkout item terpilih, satu kurir, transaksi dan webhook Paywuz tervalidasi serta idempoten, workflow pengiriman, bukti tiba, invoice, serta otorisasi lintas akun.

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

URL file upload menggunakan path same-origin `/storage/...` melalui `PUBLIC_STORAGE_URL=/storage`. Dengan demikian katalog tidak bergantung pada nilai `APP_URL` untuk menampilkan foto. `APP_URL` production tetap harus diisi `https://toko.sipaduhok.id` karena dipakai Laravel untuk URL email dan proses CLI.

Checklist deployment production lengkap tersedia di [`docs/deployment-production.md`](docs/deployment-production.md).
