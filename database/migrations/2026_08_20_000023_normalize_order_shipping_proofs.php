<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_shipping_proofs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('stage', 30);
            $table->string('path');
            $table->text('note')->nullable();
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->timestamps();
            $table->index(['order_id', 'stage']);
        });

        $now = now();
        DB::table('orders')
            ->select(['id', 'packing_proof_path', 'packing_note', 'pickup_proof_path', 'pickup_note', 'delivery_proof_path', 'delivery_note'])
            ->orderBy('id')
            ->each(function ($order) use ($now) {
                $proofs = [];

                foreach ([
                    [$order->packing_proof_path, $order->packing_note, 'dispatch', 0],
                    [$order->pickup_proof_path, $order->pickup_note, 'dispatch', 1],
                    [$order->delivery_proof_path, $order->delivery_note, 'delivery', 0],
                ] as [$path, $note, $stage, $sortOrder]) {
                    if ($path) {
                        $proofs[] = [
                            'order_id' => $order->id,
                            'uploaded_by' => null,
                            'stage' => $stage,
                            'path' => $path,
                            'note' => $note,
                            'sort_order' => $sortOrder,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }
                }

                if ($proofs !== []) {
                    DB::table('order_shipping_proofs')->insert($proofs);
                }
            });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'packing_proof_path',
                'packing_note',
                'pickup_proof_path',
                'pickup_note',
                'delivery_proof_path',
                'delivery_note',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('packing_proof_path')->nullable()->after('ready_at');
            $table->text('packing_note')->nullable()->after('packing_proof_path');
            $table->string('pickup_proof_path')->nullable()->after('dispatched_at');
            $table->text('pickup_note')->nullable()->after('pickup_proof_path');
            $table->string('delivery_proof_path')->nullable()->after('delivered_at');
            $table->text('delivery_note')->nullable()->after('delivery_proof_path');
        });

        DB::table('orders')->orderBy('id')->each(function ($order) {
            $dispatchProof = DB::table('order_shipping_proofs')->where('order_id', $order->id)->where('stage', 'dispatch')->orderBy('sort_order')->first();
            $deliveryProof = DB::table('order_shipping_proofs')->where('order_id', $order->id)->where('stage', 'delivery')->orderBy('sort_order')->first();

            DB::table('orders')->where('id', $order->id)->update([
                'pickup_proof_path' => $dispatchProof?->path,
                'pickup_note' => $dispatchProof?->note,
                'delivery_proof_path' => $deliveryProof?->path,
                'delivery_note' => $deliveryProof?->note,
            ]);
        });

        Schema::dropIfExists('order_shipping_proofs');
    }
};
