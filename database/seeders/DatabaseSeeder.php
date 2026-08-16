<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Courier;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        Courier::updateOrCreate(
            ['code' => 'main'],
            ['name' => 'Kurir Koperasi', 'fee' => 10000, 'estimate' => 'Diantar pada hari sekolah', 'is_active' => true],
        );

        $adminEmail = env('ADMIN_EMAIL', app()->environment(['local', 'testing']) ? 'admin@koperasi.test' : null);
        $adminPassword = env('ADMIN_PASSWORD', app()->environment(['local', 'testing']) ? 'password' : null);

        if ($adminEmail && $adminPassword) {
            User::updateOrCreate(
                ['email' => $adminEmail],
                [
                    'name' => 'Admin Koperasi',
                    'phone' => '080000000000',
                    'role' => UserRole::Admin,
                    'password' => $adminPassword,
                ],
            );
        }

        if (app()->environment(['local', 'testing'])) {
            User::updateOrCreate(
                ['email' => 'pembeli@koperasi.test'],
                [
                    'name' => 'Pembeli Demo',
                    'phone' => '081234567890',
                    'role' => UserRole::Buyer,
                    'password' => 'password',
                ],
            );

            $products = [
                ['Buku Tulis 38 Lembar', 'buku-tulis-38-lembar', 'buku', 5000, 100],
                ['Buku Paket Matematika', 'buku-paket-matematika', 'buku', 65000, 25],
                ['Pensil 2B', 'pensil-2b', 'alat_tulis', 3500, 80],
                ['Paket Alat Tulis', 'paket-alat-tulis', 'alat_tulis', 25000, 30],
                ['Dasi Sekolah', 'dasi-sekolah', 'atribut_sekolah', 18000, 40],
                ['Topi Sekolah', 'topi-sekolah', 'atribut_sekolah', 25000, 35],
            ];

            foreach ($products as [$name, $slug, $category, $price, $stock]) {
                Product::updateOrCreate(
                    ['slug' => $slug],
                    compact('name', 'category', 'price', 'stock') + [
                        'description' => "Produk resmi Koperasi Sipaduhok: {$name}.",
                        'is_active' => true,
                    ],
                );
            }
        }
    }
}
