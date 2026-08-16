<x-layouts.app title="Pesanan {{ $order->invoice_number }}">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div><p class="text-sm font-bold uppercase tracking-wide text-orange-600">Detail Pesanan</p><h1 class="mt-1 font-mono text-2xl font-black text-slate-900">{{ $order->invoice_number }}</h1><p class="mt-1 text-slate-500">{{ $order->created_at->format('d/m/Y H:i') }}</p></div>
        <div class="flex gap-2"><a href="{{ route('orders.invoice', $order) }}" class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-bold">Lihat Invoice</a><span class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-bold text-white">{{ $order->statusLabel() }}</span></div>
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-[1fr_340px]">
        <div class="space-y-6">
            <section class="rounded-2xl border border-slate-200 bg-white p-5">
                <h2 class="font-black text-slate-900">Item Pesanan</h2>
                <div class="mt-4 divide-y divide-slate-100">@foreach($order->items as $item)<div class="flex justify-between gap-4 py-3 text-sm"><span>{{ $item->product_name }} × {{ $item->quantity }}</span><span class="font-bold">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</span></div>@endforeach</div>
                <div class="mt-3 space-y-2 border-t border-slate-200 pt-4 text-sm"><div class="flex justify-between"><span>Subtotal</span><span>Rp {{ number_format($order->subtotal, 0, ',', '.') }}</span></div><div class="flex justify-between"><span>Kurir Koperasi</span><span>Rp {{ number_format($order->shipping_cost, 0, ',', '.') }}</span></div><div class="flex justify-between text-lg font-black"><span>Total</span><span>Rp {{ number_format($order->total, 0, ',', '.') }}</span></div></div>
            </section>

            @if($order->delivery_proof_path)
                <section class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5">
                    <h2 class="font-black text-emerald-900">Bukti Paket Tiba</h2>
                    <img src="{{ Storage::disk('public')->url($order->delivery_proof_path) }}" alt="Bukti paket tiba" class="mt-4 max-h-96 w-full rounded-xl object-contain bg-white">
                    @if($order->delivery_note)<p class="mt-3 text-sm text-emerald-800">{{ $order->delivery_note }}</p>@endif
                    @if($order->canBeConfirmedByBuyer())
                        <form method="POST" action="{{ route('orders.confirm-received', $order) }}" class="mt-4">@csrf<button class="w-full rounded-xl bg-emerald-600 px-4 py-3 font-bold text-white">Konfirmasi Barang Sudah Diterima</button></form>
                    @endif
                </section>
            @endif

            <section class="rounded-2xl border border-slate-200 bg-white p-5"><h2 class="font-black text-slate-900">Riwayat Status</h2><ol class="mt-4 space-y-3">@foreach($order->histories as $history)<li class="border-l-2 border-orange-400 pl-4 text-sm"><p class="font-bold text-slate-900">{{ str($history->to_status)->replace('_', ' ')->title() }}</p><p class="text-slate-500">{{ $history->created_at->format('d/m/Y H:i') }} · {{ $history->actor?->name ?? 'Sistem' }}</p>@if($history->note)<p class="mt-1 text-slate-600">{{ $history->note }}</p>@endif</li>@endforeach</ol></section>
        </div>

        <aside class="space-y-5">
            <section class="rounded-2xl border border-slate-200 bg-white p-5"><h2 class="font-black">Pembayaran</h2><dl class="mt-4 space-y-3 text-sm"><div class="flex justify-between gap-4"><dt>Status</dt><dd class="font-bold">{{ strtoupper($order->payment_status->value) }}</dd></div><div class="flex justify-between gap-4"><dt>Metode</dt><dd class="font-bold">{{ $order->payment_method->label() }}</dd></div><div><dt class="text-slate-500">Referensi</dt><dd class="mt-1 break-all font-mono text-xs">{{ $order->payment_reference ?: '-' }}</dd></div></dl>@if($order->payment_gateway === 'placeholder')<p class="mt-4 rounded-lg bg-amber-50 p-3 text-xs text-amber-800">Mode placeholder: admin mengonfirmasi pembayaran dari dashboard.</p>@endif</section>
            <section class="rounded-2xl border border-slate-200 bg-white p-5"><h2 class="font-black">Pengantaran</h2><dl class="mt-4 space-y-2 text-sm"><dt class="text-slate-500">Kurir</dt><dd class="font-bold">{{ $order->courier_name }}</dd><dt class="pt-2 text-slate-500">Penerima / siswa</dt><dd>{{ $order->buyer_name }} · {{ $order->student_name }} ({{ $order->class_name }})</dd><dt class="pt-2 text-slate-500">Alamat</dt><dd class="whitespace-pre-line">{{ $order->delivery_address }}</dd><dt class="pt-2 text-slate-500">Nomor HP</dt><dd>{{ $order->phone }}</dd></dl></section>
        </aside>
    </div>
</x-layouts.app>
