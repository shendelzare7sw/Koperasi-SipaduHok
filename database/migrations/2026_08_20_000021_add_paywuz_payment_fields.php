<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_settings', function (Blueprint $table) {
            $table->text('sandbox_api_key')->nullable()->after('is_production');
            $table->text('production_api_key')->nullable()->after('sandbox_api_key');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->string('gateway_payment_method', 50)->nullable()->after('payment_token');
            $table->text('payment_url')->nullable()->after('gateway_payment_method');
            $table->unsignedBigInteger('gateway_total')->nullable()->after('payment_url');
            $table->string('payment_environment', 20)->nullable()->after('gateway_total');
            $table->timestamp('payment_expires_at')->nullable()->after('payment_environment');
            $table->timestamp('gateway_settled_at')->nullable()->after('payment_expires_at');
        });

        Schema::create('payment_webhook_deliveries', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 30);
            $table->string('delivery_id', 100)->unique();
            $table->string('event', 100);
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->string('payload_hash', 64);
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_webhook_deliveries');

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'gateway_payment_method',
                'payment_url',
                'gateway_total',
                'payment_environment',
                'payment_expires_at',
                'gateway_settled_at',
            ]);
        });

        Schema::table('payment_settings', function (Blueprint $table) {
            $table->dropColumn(['sandbox_api_key', 'production_api_key']);
        });
    }
};
