<x-layouts.app title="Pesanan {{ $order->invoice_number }}">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <p class="text-sm font-bold uppercase tracking-wide text-primary">Detail Pesanan</p>
            <h1 class="mt-1 font-mono text-2xl font-black text-slate-900">{{ $order->invoice_number }}</h1>
            <p class="mt-1 text-slate-500">{{ $order->created_at->format('d/m/Y H:i') }}</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('orders.invoice', $order) }}" class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-bold">Lihat Invoice</a>
            <x-status-badge :status="$order->status" class="px-4 py-2 text-sm" />
        </div>
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-[1fr_340px]">
        <div class="space-y-6">
            <section class="rounded-2xl border border-slate-200 bg-white p-5">
                <h2 class="font-black text-slate-900">Item Pesanan</h2>
                <div class="mt-4 divide-y divide-slate-100">
                    @foreach($order->items as $item)
                        <div class="flex justify-between gap-4 py-3 text-sm"><span>{{ $item->product_name }} × {{ $item->quantity }}</span><span class="font-bold">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</span></div>
                    @endforeach
                </div>
                <div class="mt-3 space-y-2 border-t border-slate-200 pt-4 text-sm">
                    <div class="flex justify-between"><span>Subtotal</span><span>Rp {{ number_format($order->subtotal, 0, ',', '.') }}</span></div>
                    <div class="flex justify-between"><span>Kurir Toko</span><span>Rp {{ number_format($order->shipping_cost, 0, ',', '.') }}</span></div>
                    <div class="flex justify-between text-lg font-black"><span>Total</span><span>Rp {{ number_format($order->total, 0, ',', '.') }}</span></div>
                </div>
            </section>

            @if($order->status === App\Enums\OrderStatus::Completed)
                <section class="rounded-2xl border border-slate-200 bg-white p-5">
                    <h2 class="font-black text-slate-900">Ulasan Produk</h2>
                    <p class="mt-1 text-xs text-slate-500">Bagikan pengalaman untuk pembelian yang sudah selesai.</p>
                    <div class="mt-4 divide-y divide-slate-100">
                        @foreach($order->items as $item)
                            <div class="flex flex-col gap-3 py-4 sm:flex-row sm:items-center sm:justify-between">
                                <div><p class="font-bold text-slate-900">{{ $item->product_name }}</p>@if($item->review)<p class="mt-1 text-xs text-accent-yellow">@for($star = 1; $star <= 5; $star++)<i class="{{ $star <= $item->review->rating ? 'fas' : 'far' }} fa-star"></i>@endfor <span class="ml-1 text-slate-500">Ulasan tersimpan</span></p>@else<p class="mt-1 text-xs text-slate-500">Belum diulas</p>@endif</div>
                                @if($item->product)<a href="{{ route('reviews.edit', $item) }}" class="rounded-xl border border-primary px-4 py-2 text-center text-sm font-bold text-primary hover:bg-blue-50">{{ $item->review ? 'Edit Ulasan' : 'Beri Ulasan' }}</a>@endif
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif

            @if($order->dispatchProofs->isNotEmpty())<x-shipping-proof :proofs="$order->dispatchProofs" title="Paket Mulai Diantar" stage="dispatch" />@endif
            @if($order->deliveryProofs->isNotEmpty())
                <x-shipping-proof :proofs="$order->deliveryProofs" title="Paket Tiba di Alamat" stage="delivery" />
                @if($order->canBeConfirmedByBuyer())
                    <form method="POST" action="{{ route('orders.confirm-received', $order) }}" data-confirm="Pesanan akan ditandai selesai dan tidak dapat kembali ke status pengiriman." data-confirm-title="Barang sudah diterima?" data-confirm-button="Ya, sudah diterima">@csrf<button class="w-full rounded-xl bg-secondary px-4 py-3 font-bold text-white">Konfirmasi Barang Sudah Diterima</button></form>
                @endif
            @endif

            <section class="rounded-2xl border border-slate-200 bg-white p-5">
                <h2 class="font-black text-slate-900">Riwayat Status</h2>
                <ol class="mt-4 space-y-4">
                    @foreach($order->histories as $history)
                        <li class="border-l-2 border-primary pl-4 text-sm">
                            <x-status-badge :status="$history->to_status" />
                            <p class="mt-2 text-slate-500">{{ $history->created_at->format('d/m/Y H:i') }} · {{ $history->actor?->name ?? 'Sistem' }}</p>
                            @if($history->note)<p class="mt-1 text-slate-600">{{ $history->note }}</p>@endif
                        </li>
                    @endforeach
                </ol>
            </section>
        </div>

        <aside class="space-y-5">
            <section class="rounded-2xl border border-slate-200 bg-white p-5">
                <h2 class="font-black">Pembayaran</h2>
                <dl class="mt-4 space-y-3 text-sm">
                    <div class="flex flex-wrap items-center justify-between gap-3"><dt>Status</dt><dd><x-status-badge :status="$order->payment_status" type="payment" /></dd></div>
                    <div class="flex justify-between gap-4"><dt>Metode</dt><dd class="font-bold">Pembayaran Digital</dd></div>
                    <div class="flex justify-between gap-4"><dt>Kanal</dt><dd class="font-bold">{{ $order->gateway_payment_method ?: $order->payment_method->label() }}</dd></div>
                    <div><dt class="text-slate-500">Referensi</dt><dd class="mt-1 break-all font-mono text-xs">{{ $order->payment_reference ?: '-' }}</dd></div>
                </dl>

                @if($order->payment_gateway === 'paywuz' && ! in_array($order->payment_status, [App\Enums\PaymentStatus::Paid, App\Enums\PaymentStatus::Expired, App\Enums\PaymentStatus::Failed], true))
                    @unless($order->gateway_settled_at)<a href="{{ route('orders.payment', $order) }}" class="mt-5 block rounded-xl bg-primary px-4 py-3 text-center font-black text-white hover:bg-secondary">Selesaikan Pembayaran</a>@endunless
                    @unless($order->gateway_settled_at)<a href="{{ route('orders.change-payment-method', $order) }}" class="mt-2 block rounded-xl border border-primary px-4 py-2.5 text-center text-sm font-bold text-primary hover:bg-blue-50"><i class="fas fa-rotate mr-2" aria-hidden="true"></i>Ganti Metode Bayar</a>@endunless
                    <form method="POST" action="{{ route('orders.sync-payment', $order) }}" class="mt-2" data-confirm="Sistem akan memeriksa status transaksi terbaru dari penyedia pembayaran." data-confirm-title="Cek status pembayaran?" data-confirm-button="Ya, cek status">@csrf<button class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-bold text-slate-700">Cek Status Pembayaran</button></form>
                    @unless($order->gateway_settled_at)<form method="POST" action="{{ route('orders.cancel-payment', $order) }}" class="mt-2" data-confirm="Transaksi pending akan dibatalkan dan pesanan tidak dapat dibayar lagi." data-confirm-title="Batalkan pembayaran?" data-confirm-button="Ya, batalkan">@csrf<button class="w-full rounded-xl border border-red-200 px-4 py-2.5 text-sm font-bold text-red-600 hover:bg-red-50">Batalkan Pembayaran</button></form>@endunless
                @elseif($order->payment_gateway === 'placeholder')
                    <p class="mt-4 rounded-lg bg-amber-50 p-3 text-xs text-amber-800">Mode konfirmasi internal: admin toko mengonfirmasi pembayaran dari dashboard.</p>
                @endif
            </section>
            <section class="rounded-2xl border border-slate-200 bg-white p-5"><h2 class="font-black">Pengantaran</h2><dl class="mt-4 space-y-2 text-sm"><dt class="text-slate-500">Kurir</dt><dd class="font-bold">{{ $order->courier_name }}</dd><dt class="pt-2 text-slate-500">Penerima / siswa</dt><dd>{{ $order->buyer_name }} · {{ $order->student_name }} ({{ $order->class_name }})</dd><dt class="pt-2 text-slate-500">Alamat</dt><dd class="whitespace-pre-line">{{ $order->delivery_address }}</dd><dt class="pt-2 text-slate-500">Nomor HP</dt><dd>{{ $order->phone }}</dd></dl>@if($order->delivery_maps_url)<a href="{{ $order->delivery_maps_url }}" target="_blank" rel="noopener noreferrer" class="mt-4 inline-flex items-center gap-2 rounded-xl bg-blue-50 px-4 py-2.5 text-xs font-bold text-primary"><i class="fas fa-map-location-dot"></i>Lihat Titik Pengantaran</a>@endif</section>
        </aside>
    </div>
</x-layouts.app>
