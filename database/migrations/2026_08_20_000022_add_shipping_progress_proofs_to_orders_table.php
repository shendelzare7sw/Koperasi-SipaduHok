<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('packing_proof_path')->nullable()->after('ready_at');
            $table->text('packing_note')->nullable()->after('packing_proof_path');
            $table->string('pickup_proof_path')->nullable()->after('dispatched_at');
            $table->text('pickup_note')->nullable()->after('pickup_proof_path');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['packing_proof_path', 'packing_note', 'pickup_proof_path', 'pickup_note']);
        });
    }
};
