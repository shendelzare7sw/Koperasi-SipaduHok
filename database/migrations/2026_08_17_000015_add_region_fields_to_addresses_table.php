<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('addresses', function (Blueprint $table) {
            $table->string('village', 100)->nullable()->after('full_address');
            $table->string('district', 100)->nullable()->after('village');
            $table->string('city', 100)->nullable()->after('district');
            $table->string('province', 100)->nullable()->after('city');
            $table->string('postal_code', 10)->nullable()->after('province');
        });
    }

    public function down(): void
    {
        Schema::table('addresses', function (Blueprint $table) {
            $table->dropColumn(['village', 'district', 'city', 'province', 'postal_code']);
        });
    }
};
