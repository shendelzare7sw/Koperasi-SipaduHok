<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('store_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        $now = now();
        DB::table('store_settings')->insert([
            ['key' => 'legal_name', 'value' => 'Toko Sipaduhok', 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'support_email', 'value' => 'toko@sipaduhok.id', 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'phone', 'value' => '', 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'whatsapp', 'value' => '', 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'address', 'value' => 'Alamat toko belum diatur.', 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'operating_hours', 'value' => 'Senin–Jumat pada jam operasional toko', 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'description', 'value' => 'Toko kebutuhan sekolah yang menyediakan buku, alat tulis, dan atribut sekolah.', 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('store_settings');
    }
};
