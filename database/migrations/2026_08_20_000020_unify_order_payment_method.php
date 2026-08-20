<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('orders')) {
            return;
        }

        DB::table('orders')
            ->whereIn('payment_method', ['qris', 'virtual_account'])
            ->update(['payment_method' => 'payment_gateway']);
    }

    public function down(): void
    {
        if (! Schema::hasTable('orders')) {
            return;
        }

        DB::table('orders')
            ->where('payment_method', 'payment_gateway')
            ->update(['payment_method' => 'qris']);
    }
};
