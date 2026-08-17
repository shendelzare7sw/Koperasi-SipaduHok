# Rekonsiliasi Fitur DigiRack untuk Koperasi Sipaduhok

Dokumen ini mencatat hasil audit terhadap `C:\laragon\www\digirack` dan keputusan adaptasi untuk Koperasi Sipaduhok. Koperasi hanya memiliki dua role (`admin` sebagai satu-satunya penjual dan `pembeli`) sehingga fitur marketplace tidak disalin mentah.

## Fitur yang diadaptasi

| Fitur DigiRack | Adaptasi Koperasi Sipaduhok |
|---|---|
| Profil dan pengaturan akun | Satu halaman profil dan keamanan yang dipakai admin maupun pembeli. Role bersifat hanya-baca. |
| Dropdown akun dan notifikasi | Dropdown avatar, bel, badge belum dibaca, pusat notifikasi, baca satu, dan baca semua. |
| Notifikasi transaksi | Pesanan baru, pembayaran, status pengiriman, bukti tiba, penerimaan, ulasan, dan balasan ulasan. |
| Alamat pembeli | CRUD alamat lengkap (penerima, telepon, jalan, kelurahan/desa, kecamatan, kota/kabupaten, provinsi, dan kode pos) yang diisi manual tanpa API wilayah/ongkir. Checkout wajib memakai alamat tersimpan dan alamat utama dipilih otomatis. |
| Wishlist | Wishlist khusus pembeli dengan badge dan tombol hati pada katalog/detail. |
| Galeri produk | Maksimal lima foto, preview sebelum upload, thumbnail, lightbox, dan penghapusan foto oleh admin. |
| Ulasan produk | Hanya pembeli dengan order selesai yang dapat mengulas; admin koperasi dapat memberi balasan resmi. |
| Filter katalog | Pencarian, kategori, rentang harga, rating, dan pengurutan harga/rating/stok. |
| Produk terkait | Rekomendasi dari kategori yang sama pada detail produk. |
| Keamanan akun | Registrasi dan pemulihan akun memakai OTP email; login, registrasi, serta pemulihan dilindungi Cloudflare Turnstile. |
| Navigasi halaman | Tombol kembali kontekstual tersedia pada halaman turunan, dengan logo transparan tanpa kotak pembungkus pada header dan footer. |
| Halaman publik/legal | Tentang, bantuan, pembayaran, pengiriman, pengembalian, privasi, dan syarat ketentuan. |
| Pengaturan identitas toko | Nama legal, email, telepon, WhatsApp, alamat, jam layanan, dan deskripsi dikelola admin. |
| Label pengiriman | Label cetak khusus Kurir Koperasi, tanpa resi atau API ekspedisi eksternal. |
| Preview bukti tiba | Preview sebelum upload serta lightbox bagi admin dan pembeli setelah bukti tersimpan. |

## Fitur yang sengaja tidak dibawa

| Fitur DigiRack | Alasan |
|---|---|
| Role seller dan role switcher | Admin koperasi adalah satu-satunya seller. |
| Store onboarding/verifikasi seller | Tidak ada seller eksternal. |
| Escrow, wallet seller, payout, dan Midtrans IRIS | Pembayaran langsung masuk ke entitas koperasi. |
| RajaOngkir/Komerce dan ekspedisi reguler | Pengiriman hanya memakai satu Kurir Koperasi dengan tarif admin. |
| Multi-store checkout dan storefront seller | Hanya ada satu toko koperasi. |
| Fee marketplace dan moderasi produk seller | Tidak relevan pada model satu penjual. |
| Flash sale dan banner marketplace | Tidak termasuk kebutuhan inti saat ini; dapat ditambahkan setelah katalog operasional stabil. |
| Pembatalan otomatis transaksi Midtrans | Membatalkan transaksi berbayar memerlukan aturan refund dan sinkronisasi gateway agar tidak menghasilkan status uang yang keliru. Saat ini pembeli diarahkan menghubungi admin sesuai kebijakan publik. |
| Retur otomatis dan refund gateway | Memerlukan prosedur operasional serta keputusan refund koperasi; halaman kebijakan dan kontak sudah disiapkan terlebih dahulu. |
| Auto-complete pesanan | Belum diaktifkan karena alur yang diminta mewajibkan konfirmasi internal pembeli setelah admin mengunggah bukti tiba. |

## Prinsip yang dipertahankan

- Stok berkurang hanya setelah pembayaran terkonfirmasi.
- Tidak ada escrow atau saldo tertahan.
- Kurir hanya Kurir Koperasi dan tarifnya diatur admin.
- Admin mengunggah bukti paket tiba, lalu pembeli mengonfirmasi penerimaan.
- Semua aksi penting memakai konfirmasi SweetAlert dan histori pesanan tetap menjadi jejak internal.
