<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number')->nullable()->unique();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('buyer_name');
            $table->string('student_name');
            $table->string('class_name', 100);
            $table->string('phone', 20);
            $table->foreignId('courier_id')->nullable()->constrained()->nullOnDelete();
            $table->string('courier_name')->nullable();
            $table->unsignedBigInteger('shipping_cost')->default(0);
            $table->text('delivery_address')->nullable();
            $table->string('status', 30)->default('pending_payment')->index();
            $table->string('payment_method', 30);
            $table->string('payment_gateway', 30)->default('placeholder');
            $table->string('payment_status', 30)->default('unpaid')->index();
            $table->string('payment_reference')->nullable()->index();
            $table->text('payment_token')->nullable();
            $table->unsignedBigInteger('subtotal');
            $table->unsignedBigInteger('total');
            $table->text('notes')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('ready_at')->nullable();
            $table->timestamp('dispatched_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->string('delivery_proof_path')->nullable();
            $table->text('delivery_note')->nullable();
            $table->timestamp('received_confirmed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
