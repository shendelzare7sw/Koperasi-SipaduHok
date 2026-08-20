<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('addresses', function (Blueprint $table) {
            $table->string('province_code', 2)->nullable()->after('province');
            $table->string('city_code', 5)->nullable()->after('province_code');
            $table->string('district_code', 8)->nullable()->after('city_code');
            $table->string('village_code', 13)->nullable()->after('district_code');
            $table->string('street', 255)->nullable()->after('full_address');
            $table->string('house_number', 50)->nullable()->after('street');
            $table->string('rt', 3)->nullable()->after('house_number');
            $table->string('rw', 3)->nullable()->after('rt');
            $table->text('landmark')->nullable()->after('rw');
            $table->decimal('latitude', 10, 7)->nullable()->after('postal_code');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            $table->index(['province_code', 'city_code']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('delivery_latitude', 10, 7)->nullable()->after('delivery_address');
            $table->decimal('delivery_longitude', 10, 7)->nullable()->after('delivery_latitude');
            $table->text('delivery_maps_url')->nullable()->after('delivery_longitude');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['delivery_latitude', 'delivery_longitude', 'delivery_maps_url']);
        });

        Schema::table('addresses', function (Blueprint $table) {
            $table->dropIndex(['province_code', 'city_code']);
            $table->dropColumn([
                'province_code', 'city_code', 'district_code', 'village_code',
                'street', 'house_number', 'rt', 'rw', 'landmark', 'latitude', 'longitude',
            ]);
        });
    }
};
