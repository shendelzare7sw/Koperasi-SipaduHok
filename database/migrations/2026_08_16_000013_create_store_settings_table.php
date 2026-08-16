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
            ['key' => 'legal_name', 'value' => 'Koperasi Sipaduhok', 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'support_email', 'value' => 'koperasi@sipaduhok.id', 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'phone', 'value' => '', 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'whatsapp', 'value' => '', 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'address', 'value' => 'Alamat sekolah belum diatur.', 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'operating_hours', 'value' => 'Senin–Jumat pada jam operasional sekolah', 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'description', 'value' => 'Koperasi sekolah yang menyediakan buku, alat tulis, dan atribut sekolah.', 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('store_settings');
    }
};
