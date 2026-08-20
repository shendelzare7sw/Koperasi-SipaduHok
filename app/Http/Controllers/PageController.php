<?php

namespace App\Http\Controllers;

use App\Models\StoreSetting;
use Illuminate\View\View;

class PageController extends Controller
{
    public function about(): View
    {
        return $this->page('Tentang Toko', 'Identitas usaha', [
            ['title' => 'Toko kebutuhan sekolah tepercaya', 'paragraphs' => ['Toko Sipaduhok adalah toko mandiri yang menyediakan buku, alat tulis, atribut sekolah, dan kebutuhan belajar lainnya.', 'Seluruh katalog, transaksi, pembayaran, invoice, dukungan pelanggan, dan pengiriman dikelola langsung melalui toko ini.']],
            ['title' => 'Model layanan', 'items' => ['Produk dijual dan dikelola langsung oleh Toko Sipaduhok.', 'Toko tidak mengoperasikan escrow atau saldo internal antar pengguna; pembayaran dan settlement diproses oleh Paywuz.', 'Pengiriman dilakukan oleh Kurir Toko dengan tarif yang ditampilkan saat checkout.', 'Bukti paket tiba diunggah oleh admin dan dapat dikonfirmasi pembeli.']],
        ]);
    }

    public function help(): View
    {
        return $this->page('Pusat Bantuan', 'Dukungan pembeli', [
            ['title' => 'Butuh bantuan pesanan?', 'paragraphs' => ['Siapkan nomor invoice ketika menghubungi toko. Tim kami dapat membantu pemeriksaan pembayaran, ketersediaan stok, status pengiriman, invoice, atau bukti paket tiba.']],
            ['title' => 'Jam layanan', 'items' => ['Layanan mengikuti jam operasional yang tercantum di situs.', 'Pesan di luar jam layanan akan ditangani pada hari kerja berikutnya.', 'Gunakan hanya kontak resmi yang tercantum pada halaman ini.']],
        ]);
    }

    public function payment(): View
    {
        return $this->page('Cara Pembayaran', 'Pembayaran langsung', [
            ['title' => 'Kanal pembayaran digital', 'paragraphs' => ['Saat checkout, pembeli memilih kanal Paywuz yang sedang aktif. Untuk Virtual Account, bank dipilih pada halaman pembayaran resmi Paywuz. Status pembayaran diperbarui otomatis melalui webhook aman.']],
            ['title' => 'Ketentuan pembayaran', 'items' => ['Tagihan toko dan biaya kanal ditampilkan sebelum pembayaran diselesaikan.', 'Pesanan diproses setelah Paywuz menyatakan dana telah masuk ke saldo merchant.', 'Toko tidak menggunakan escrow, wallet seller, atau penitipan dana antar pengguna.', 'Jika status belum berubah setelah pembayaran, gunakan tombol cek status atau hubungi admin dengan nomor invoice.']],
        ]);
    }

    public function shipping(): View
    {
        return $this->page('Kebijakan Pengiriman', 'Kurir Toko', [
            ['title' => 'Layanan pengiriman toko', 'paragraphs' => ['Seluruh pesanan dikirim menggunakan Kurir Toko. Tarif pengiriman ditampilkan secara transparan saat checkout dan diatur oleh admin toko.']],
            ['title' => 'Alur pengiriman', 'items' => ['Pesanan disiapkan setelah pembayaran terkonfirmasi.', 'Admin mengubah status menjadi siap dikirim dan dalam pengantaran.', 'Admin mengunggah foto bukti ketika paket tiba di alamat.', 'Pembeli memeriksa bukti dan mengonfirmasi bahwa barang sudah diterima.']],
        ]);
    }

    public function returns(): View
    {
        return $this->page('Kebijakan Pembatalan & Pengembalian', 'Perlindungan pembeli', [
            ['title' => 'Pembatalan', 'paragraphs' => ['Permintaan pembatalan perlu diajukan kepada admin toko sebelum pesanan masuk proses pengiriman. Pesanan yang pembayarannya telah diproses akan diperiksa berdasarkan status payment gateway dan kesiapan barang.']],
            ['title' => 'Barang bermasalah', 'items' => ['Laporkan barang salah, rusak, atau tidak lengkap sesegera mungkin dengan nomor invoice dan foto pendukung.', 'Jangan membuang kemasan sebelum pemeriksaan admin selesai.', 'Penggantian atau pengembalian dana diputuskan toko setelah verifikasi.', 'Produk yang sudah digunakan atau rusak karena kesalahan pemakaian tidak dapat dikembalikan.']],
        ]);
    }

    public function privacy(): View
    {
        return $this->page('Kebijakan Privasi', 'Perlindungan data', [
            ['title' => 'Data yang diproses', 'paragraphs' => ['Sistem menyimpan nama, email, nomor HP, data siswa/kelas, alamat pengiriman, detail pesanan, status pembayaran, serta data verifikasi identitas berupa nama sesuai KTP, NIK, dan foto KTP yang dibutuhkan untuk pemeriksaan akun pembeli.']],
            ['title' => 'Penggunaan dan keamanan', 'items' => ['Data digunakan untuk autentikasi, verifikasi identitas, pemrosesan pesanan, pembayaran, pengiriman, invoice, dan dukungan.', 'NIK dienkripsi dan foto KTP disimpan pada storage privat yang hanya dapat dibuka pemilik akun serta admin berwenang.', 'Toko tidak menjual data pribadi kepada pihak lain.', 'Data pembayaran sensitif diproses oleh payment gateway; sistem toko hanya menyimpan referensi dan status transaksi.', 'Pengguna bertanggung jawab menjaga kerahasiaan kata sandi akun.']],
        ]);
    }

    public function terms(): View
    {
        return $this->page('Syarat & Ketentuan', 'Ketentuan penggunaan', [
            ['title' => 'Penggunaan layanan', 'items' => ['Pengguna harus memberikan data akun, KTP, penerima, dan alamat yang benar.', 'Checkout hanya tersedia setelah verifikasi KTP disetujui admin toko.', 'Harga, stok, tarif kurir, dan ketersediaan produk mengikuti informasi saat checkout.', 'Invoice elektronik merupakan catatan transaksi resmi Toko Sipaduhok.', 'Penyalahgunaan akun atau transaksi dapat menyebabkan pembatasan layanan.']],
            ['title' => 'Penyelesaian kendala', 'paragraphs' => ['Kendala transaksi diselesaikan langsung antara pembeli dan admin Toko Sipaduhok berdasarkan invoice, status pembayaran, histori pesanan, dan bukti pengiriman di dalam sistem.']],
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
