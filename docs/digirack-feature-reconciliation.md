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
| Arsip produk | Produk memakai soft delete, tersedia menu Arsip Produk, pencarian, pemulihan, dan hapus permanen beserta file galeri. |
| Kelola pengguna | Disederhanakan menjadi Kelola Pembeli: pencarian, filter status akun/KTP, detail akun, alamat, transaksi, nonaktifkan/aktifkan kembali, dan pencabutan sesi saat dinonaktifkan. |
| Verifikasi identitas seller | Dialihkan menjadi verifikasi identitas pembeli sebelum checkout. Nama legal dan NIK dienkripsi, hash NIK mencegah satu NIK dipakai lintas akun, foto KTP disimpan di disk privat, dan admin menyetujui/menolak disertai catatan. |
| Pengaturan Midtrans | Panel admin khusus mengatur aktivasi, Sandbox/Production, Server Key, Client Key, dan Merchant ID. Key terenkripsi, tidak ditampilkan ulang, perubahan wajib dikonfirmasi dengan kata sandi admin, dan format key divalidasi terhadap environment. |
| Status pengguna | Admin dapat menonaktifkan akun pembeli. Login berikutnya ditolak dan sesi yang sedang aktif dicabut. |
| Laporan seller | Dipadatkan menjadi laporan penjualan koperasi berdasarkan periode, omzet selesai, biaya kurir, jumlah barang, serta produk terlaris. |

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
| Recovery admin tersembunyi dengan security question/PIN | Pola ini lebih mudah disalahgunakan dan tidak memberi jaminan kepemilikan kanal. Sistem koperasi memakai OTP email, rate limit, Turnstile, dan pencabutan sesi. |
| Penyimpanan dokumen identitas pada disk publik | Foto KTP merupakan data sensitif. Implementasi koperasi menyimpannya di `storage/app/private` dan menyajikannya hanya melalui route terautentikasi milik pembeli/admin dengan header `no-store`. |
| Kredensial gateway dalam setting plaintext | Server/Client Key koperasi memakai encrypted cast Laravel dan tidak pernah dikirim kembali ke HTML panel admin. |
| Kategori dinamis marketplace | Katalog koperasi mempertahankan kategori inti sekolah dan opsi `Lainnya` dengan nama kategori tambahan; format yang sama didukung oleh import Excel. Migrasi ke tabel kategori baru belum diperlukan. |
| Banner marketplace dan flash sale | Bukan kebutuhan transaksi inti koperasi dan menambah aturan harga/stok baru. Keduanya ditunda sampai kebijakan promo koperasi ditetapkan. |

## Ringkasan audit per area

| Area DigiRack yang diperiksa | Status pada Koperasi Sipaduhok |
|---|---|
| Katalog, detail, galeri, filter, stok, kategori | Sudah diadaptasi untuk satu seller. |
| Cart, beli langsung, checkout item terpilih | Sudah diadaptasi. Checkout mensyaratkan akun aktif, KTP terverifikasi, alamat tersimpan, dan kurir aktif. |
| Alamat dan ongkir | CRUD alamat manual sudah ada; ongkir hanya tarif flat Kurir Koperasi tanpa API eksternal. |
| Midtrans Snap, callback, dan sinkronisasi status | Sudah diadaptasi. Callback memeriksa signature dan nominal; stok diproses idempoten. |
| Invoice, label, bukti tiba, dan histori status | Sudah diadaptasi untuk alur kurir internal. |
| Wishlist, ulasan, balasan seller, dan notifikasi | Sudah diadaptasi. |
| Profil, keamanan akun, lupa akun/password | Sudah diadaptasi dengan OTP dan Turnstile; recovery tersembunyi DigiRack tidak dibawa. |
| User administration dan verifikasi identitas | Sudah diadaptasi khusus pembeli, termasuk blokir akun dan KTP privat. |
| Product archive | Sudah dilengkapi dengan restore dan hapus permanen. |
| Store/seller/multi-role | Diganti satu identitas koperasi dan dua role tetap. |
| Wallet, payout, escrow, fee, IRIS | Tidak relevan dan tidak dibawa. |
| RajaOngkir/Komerce/lokasi API | Tidak dibawa. |
| Banner, flash sale, retur/refund otomatis | Ditunda sampai ada aturan operasional resmi. |

## Prinsip yang dipertahankan

- Stok berkurang hanya setelah pembayaran terkonfirmasi.
- Tidak ada escrow atau saldo tertahan.
- Kurir hanya Kurir Koperasi dan tarifnya diatur admin.
- Admin mengunggah bukti paket tiba, lalu pembeli mengonfirmasi penerimaan.
- Semua aksi penting memakai konfirmasi SweetAlert dan histori pesanan tetap menjadi jejak internal.
- Checkout production hanya dibuka jika Midtrans aktif dan Server/Client Key lengkap. Mode placeholder dibatasi untuk environment `local`/`testing`.
- Pengajuan merchant harus menggunakan identitas, katalog, hubungan usaha, dan proses transaksi yang benar-benar dijalankan; aplikasi tidak dirancang untuk menyamarkan entitas saat onboarding gateway.
