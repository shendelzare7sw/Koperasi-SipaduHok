@props([
    'status',
    'type' => 'order',
    'label' => null,
])

@php
    $value = $status instanceof BackedEnum ? $status->value : (string) $status;
    $resolvedLabel = $label ?? match ($type) {
        'payment' => match ($value) {
            'unpaid' => 'Belum Dibayar',
            'pending' => 'Menunggu Pembayaran',
            'paid' => 'Lunas',
            'failed' => 'Gagal',
            'expired' => 'Kedaluwarsa',
            default => str($value)->replace('_', ' ')->title(),
        },
        default => match ($value) {
            'pending_payment' => 'Menunggu Pembayaran',
            'processing' => 'Diproses',
            'ready' => 'Siap Dikirim',
            'out_for_delivery' => 'Dalam Pengantaran',
            'delivered' => 'Tiba di Alamat',
            'completed' => 'Selesai',
            'cancelled' => 'Dibatalkan',
            default => str($value)->replace('_', ' ')->title(),
        },
    };

    $colors = match ($type) {
        'payment' => match ($value) {
            'paid' => 'border-emerald-200 bg-emerald-100 text-emerald-800',
            'pending' => 'border-amber-200 bg-amber-100 text-amber-800',
            'failed' => 'border-red-200 bg-red-100 text-red-800',
            'expired' => 'border-orange-200 bg-orange-100 text-orange-800',
            default => 'border-slate-200 bg-slate-100 text-slate-700',
        },
        default => match ($value) {
            'pending_payment' => 'border-amber-200 bg-amber-100 text-amber-800',
            'processing' => 'border-blue-200 bg-blue-100 text-blue-800',
            'ready' => 'border-violet-200 bg-violet-100 text-violet-800',
            'out_for_delivery' => 'border-cyan-200 bg-cyan-100 text-cyan-800',
            'delivered' => 'border-emerald-200 bg-emerald-100 text-emerald-800',
            'completed' => 'border-green-200 bg-green-100 text-green-800',
            'cancelled' => 'border-red-200 bg-red-100 text-red-800',
            default => 'border-slate-200 bg-slate-100 text-slate-700',
        },
    };
@endphp

<span {{ $attributes->class("inline-flex w-fit items-center gap-1.5 rounded-full border px-3 py-1 text-xs font-extrabold {$colors}") }} data-status-badge="{{ $type }}:{{ $value }}">
    <span class="h-1.5 w-1.5 rounded-full bg-current" aria-hidden="true"></span>
    {{ $resolvedLabel }}
</span>
