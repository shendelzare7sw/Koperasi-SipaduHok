<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** @var array<string, array{string, string}> */
    private array $settings = [
        'legal_name' => ['Koperasi Sipaduhok', 'Toko Sipaduhok'],
        'support_email' => ['koperasi@sipaduhok.id', 'toko@sipaduhok.id'],
        'address' => ['Alamat sekolah belum diatur.', 'Alamat toko belum diatur.'],
        'operating_hours' => ['Senin–Jumat pada jam operasional sekolah', 'Senin–Jumat pada jam operasional toko'],
        'description' => ['Koperasi sekolah yang menyediakan buku, alat tulis, dan atribut sekolah.', 'Toko kebutuhan sekolah yang menyediakan buku, alat tulis, dan atribut sekolah.'],
    ];

    public function up(): void
    {
        $this->replaceSettings(false);

        if (Schema::hasTable('couriers')) {
            DB::table('couriers')->where('name', 'Kurir Koperasi')->update(['name' => 'Kurir Toko']);
        }

        if (Schema::hasTable('products')) {
            DB::table('products')
                ->where('description', 'like', '%Koperasi Sipaduhok%')
                ->update(['description' => DB::raw("REPLACE(description, 'Koperasi Sipaduhok', 'Toko Sipaduhok')")]);
        }
    }

    public function down(): void
    {
        $this->replaceSettings(true);

        if (Schema::hasTable('couriers')) {
            DB::table('couriers')->where('name', 'Kurir Toko')->update(['name' => 'Kurir Koperasi']);
        }

        if (Schema::hasTable('products')) {
            DB::table('products')
                ->where('description', 'like', '%Toko Sipaduhok%')
                ->update(['description' => DB::raw("REPLACE(description, 'Toko Sipaduhok', 'Koperasi Sipaduhok')")]);
        }
    }

    private function replaceSettings(bool $reverse): void
    {
        if (! Schema::hasTable('store_settings')) {
            return;
        }

        foreach ($this->settings as $key => [$oldValue, $newValue]) {
            DB::table('store_settings')
                ->where('key', $key)
                ->where('value', $reverse ? $newValue : $oldValue)
                ->update([
                    'value' => $reverse ? $oldValue : $newValue,
                    'updated_at' => now(),
                ]);
        }
    }
};
