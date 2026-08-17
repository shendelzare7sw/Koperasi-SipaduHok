<?php

namespace App\Http\Controllers;

use App\Models\StoreSetting;
use Illuminate\View\View;

class PageController extends Controller
{
    public function about(): View
    {
        return $this->page('Tentang Koperasi', 'Identitas usaha', [
            ['title' => 'Koperasi sekolah dalam ekosistem Sipaduhok', 'paragraphs' => ['Koperasi Sipaduhok adalah toko koperasi sekolah yang berdiri sebagai layanan perdagangan terpisah dari sistem administrasi SPP di app.sipaduhok.id.', 'Produk yang dijual berfokus pada kebutuhan sekolah: buku, alat tulis, serta atribut sekolah. Semua transaksi penjualan, pembayaran, invoice, dan pengiriman dicatat di sistem koperasi ini.']],
            ['title' => 'Model layanan', 'items' => ['Satu penjual resmi: admin Koperasi Sipaduhok.', 'Pembeli bertransaksi langsung dengan koperasi tanpa escrow atau saldo tertahan.', 'Pengiriman hanya dilakukan oleh Kurir Koperasi dengan tarif yang ditetapkan admin.', 'Bukti paket tiba diunggah oleh admin dan dapat dikonfirmasi pembeli.']],
        ]);
    }

    public function help(): View
    {
        return $this->page('Pusat Bantuan', 'Dukungan pembeli', [
            ['title' => 'Butuh bantuan pesanan?', 'paragraphs' => ['Siapkan nomor invoice ketika menghubungi koperasi. Admin dapat membantu pemeriksaan pembayaran, ketersediaan stok, status pengiriman, invoice, atau bukti paket tiba.']],
            ['title' => 'Jam layanan', 'items' => ['Layanan mengikuti jam operasional sekolah.', 'Pesan di luar jam layanan akan ditangani pada hari sekolah berikutnya.', 'Kontak resmi tercantum pada panel informasi di halaman ini.']],
        ]);
    }

    public function payment(): View
    {
        return $this->page('Cara Pembayaran', 'Pembayaran langsung', [
            ['title' => 'QRIS dan Virtual Account', 'paragraphs' => ['Pembeli memilih QRIS atau Virtual Account saat checkout. Ketika payment gateway aktif, pembayaran diproses langsung atas nama Koperasi Sipaduhok dan status diperbarui otomatis melalui sistem gateway.']],
            ['title' => 'Ketentuan pembayaran', 'items' => ['Nominal yang dibayar harus sama dengan total pada invoice.', 'Pesanan diproses setelah pembayaran dinyatakan lunas.', 'Koperasi tidak menggunakan escrow, wallet seller, atau penitipan dana antar pengguna.', 'Jika status belum berubah setelah pembayaran, gunakan tombol cek status atau hubungi admin dengan nomor invoice.']],
        ]);
    }

    public function shipping(): View
    {
        return $this->page('Kebijakan Pengiriman', 'Kurir Koperasi', [
            ['title' => 'Satu layanan pengiriman internal', 'paragraphs' => ['Seluruh pesanan dikirim menggunakan Kurir Koperasi. Kami tidak menggunakan RajaOngkir maupun API ekspedisi pihak ketiga. Tarif pengiriman ditampilkan saat checkout dan diatur oleh admin koperasi.']],
            ['title' => 'Alur pengiriman', 'items' => ['Pesanan disiapkan setelah pembayaran terkonfirmasi.', 'Admin mengubah status menjadi siap dikirim dan dalam pengantaran.', 'Admin mengunggah foto bukti ketika paket tiba di alamat.', 'Pembeli memeriksa bukti dan mengonfirmasi bahwa barang sudah diterima.']],
        ]);
    }

    public function returns(): View
    {
        return $this->page('Kebijakan Pembatalan & Pengembalian', 'Perlindungan pembeli', [
            ['title' => 'Pembatalan', 'paragraphs' => ['Permintaan pembatalan perlu diajukan kepada admin koperasi sebelum pesanan masuk proses pengiriman. Pesanan yang pembayarannya telah diproses akan diperiksa berdasarkan status payment gateway dan kesiapan barang.']],
            ['title' => 'Barang bermasalah', 'items' => ['Laporkan barang salah, rusak, atau tidak lengkap sesegera mungkin dengan nomor invoice dan foto pendukung.', 'Jangan membuang kemasan sebelum pemeriksaan admin selesai.', 'Penggantian atau pengembalian dana diputuskan koperasi setelah verifikasi.', 'Produk yang sudah digunakan atau rusak karena kesalahan pemakaian tidak dapat dikembalikan.']],
        ]);
    }

    public function privacy(): View
    {
        return $this->page('Kebijakan Privasi', 'Perlindungan data', [
            ['title' => 'Data yang diproses', 'paragraphs' => ['Sistem menyimpan nama, email, nomor HP, data siswa/kelas, alamat pengiriman, detail pesanan, status pembayaran, serta data verifikasi identitas berupa nama sesuai KTP, NIK, dan foto KTP yang dibutuhkan untuk pemeriksaan akun pembeli.']],
            ['title' => 'Penggunaan dan keamanan', 'items' => ['Data digunakan untuk autentikasi, verifikasi identitas, pemrosesan pesanan, pembayaran, pengiriman, invoice, dan dukungan.', 'NIK dienkripsi dan foto KTP disimpan pada storage privat yang hanya dapat dibuka pemilik akun serta admin berwenang.', 'Koperasi tidak menjual data pribadi kepada pihak lain.', 'Data pembayaran sensitif diproses oleh payment gateway; sistem koperasi hanya menyimpan referensi dan status transaksi.', 'Pengguna bertanggung jawab menjaga kerahasiaan kata sandi akun.']],
        ]);
    }

    public function terms(): View
    {
        return $this->page('Syarat & Ketentuan', 'Ketentuan penggunaan', [
            ['title' => 'Penggunaan layanan', 'items' => ['Pengguna harus memberikan data akun, KTP, siswa, penerima, dan alamat yang benar.', 'Checkout hanya tersedia setelah verifikasi KTP disetujui admin koperasi.', 'Harga, stok, tarif kurir, dan ketersediaan produk mengikuti informasi saat checkout.', 'Invoice elektronik merupakan catatan transaksi resmi Koperasi Sipaduhok.', 'Penyalahgunaan akun atau transaksi dapat menyebabkan pembatasan layanan.']],
            ['title' => 'Penyelesaian kendala', 'paragraphs' => ['Kendala transaksi diselesaikan langsung antara pembeli dan admin Koperasi Sipaduhok berdasarkan invoice, status pembayaran, histori pesanan, dan bukti pengiriman di dalam sistem.']],
        ]);
    }

    /** @param list<array<string, mixed>> $sections */
    private function page(string $title, string $eyebrow, array $sections): View
    {
        return view('pages.info', [
            'title' => $title,
            'eyebrow' => $eyebrow,
            'sections' => $sections,
            'settings' => StoreSetting::values(),
        ]);
    }
}
