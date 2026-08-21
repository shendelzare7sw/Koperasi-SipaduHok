<x-layouts.app title="Ganti Metode Bayar {{ $order->invoice_number }}">
    @php
        $currentMethod = (string) old('gateway_payment_method', $order->gateway_payment_method);
    @endphp

    <div class="mx-auto max-w-4xl">
        <a href="{{ route('orders.show', $order) }}" class="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-bold text-slate-700 shadow-sm hover:border-primary hover:text-primary">
            <i class="fas fa-arrow-left" aria-hidden="true"></i>
            Kembali ke Detail Pesanan
        </a>

        <div class="mt-6 grid gap-6 lg:grid-cols-[1fr_320px]">
            <form method="POST" action="{{ route('orders.update-payment-method', $order) }}" class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6" x-data="{ selectedPayment: @js($currentMethod) }" data-confirm="Transaksi pembayaran lama akan dibatalkan, lalu sistem membuat kanal pembayaran baru untuk pesanan yang sama." data-confirm-title="Ganti metode pembayaran?" data-confirm-button="Ya, ganti metode">
                @csrf

                <p class="text-sm font-extrabold uppercase tracking-widest text-primary">Pembayaran</p>
                <h1 class="mt-1 text-3xl font-black text-slate-900">Ganti Metode Bayar</h1>
                <p class="mt-2 text-sm leading-6 text-slate-500">Pilih metode baru jika kanal sebelumnya belum jadi dibayar. Pesanan tidak dibuat ulang, hanya link pembayaran yang diperbarui.</p>

                @error('gateway_payment_method')
                    <div class="mt-5 rounded-xl border border-red-200 bg-red-50 p-4 text-sm font-semibold text-red-700">
                        <i class="fas fa-triangle-exclamation mr-2" aria-hidden="true"></i>{{ $message }}
                    </div>
                @enderror

                @if(count($paymentMethods) > 0)
                    <div class="mt-6 grid gap-3 sm:grid-cols-2">
                        @foreach($paymentMethods as $method)
                            @php
                                $percent = $method['fee_percent_bps'] / 100;
                                $feeParts = collect([
                                    $method['fee_flat'] > 0 ? 'Rp '.number_format($method['fee_flat'], 0, ',', '.') : null,
                                    $percent > 0 ? number_format($percent, 2, ',', '.').'%' : null,
                                ])->filter()->implode(' + ');
                                $isCurrent = $order->gateway_payment_method === $method['code'];
                                $isAvailableForTotal = $order->total >= $method['min_amount'] && $order->total <= $method['max_amount'];
                            @endphp
                            <label class="relative rounded-2xl border p-4 transition {{ $isAvailableForTotal ? 'cursor-pointer' : 'cursor-not-allowed opacity-60' }}" :class="selectedPayment === @js($method['code']) ? 'border-primary bg-blue-50 ring-2 ring-primary/10' : 'border-slate-200 hover:border-primary/40'">
                                <input type="radio" name="gateway_payment_method" value="{{ $method['code'] }}" x-model="selectedPayment" required class="sr-only" @disabled(! $isAvailableForTotal)>
                                <span class="flex items-start gap-3">
                                    <span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-primary text-white">
                                        <i class="fas {{ $method['type'] === 'qris' ? 'fa-qrcode' : 'fa-building-columns' }}" aria-hidden="true"></i>
                                    </span>
                                    <span class="min-w-0 flex-1">
                                        <span class="flex flex-wrap items-center gap-2">
                                            <span class="font-black text-slate-900">{{ $method['name'] }}</span>
                                            @if($isCurrent)
                                                <span class="rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-black uppercase tracking-wide text-amber-700">Saat ini</span>
                                            @endif
                                        </span>
                                        <span class="mt-1 block text-xs leading-5 text-slate-500">{{ $method['type'] === 'meta' ? 'Pilih bank di halaman pembayaran.' : ($feeParts ? 'Estimasi biaya mulai '.$feeParts : 'Tanpa estimasi biaya tambahan.') }}</span>
                                    </span>
                                    <span class="grid h-6 w-6 place-items-center rounded-full border-2" :class="selectedPayment === @js($method['code']) ? 'border-primary bg-primary text-white' : 'border-slate-300 text-transparent'">
                                        <i class="fas fa-check text-[10px]" aria-hidden="true"></i>
                                    </span>
                                </span>
                                <span class="mt-3 block text-[11px] text-slate-400">Batas Rp {{ number_format($method['min_amount'], 0, ',', '.') }}–Rp {{ number_format($method['max_amount'], 0, ',', '.') }}</span>
                                @unless($isAvailableForTotal)
                                    <span class="mt-2 block rounded-lg bg-red-50 px-3 py-2 text-[11px] font-semibold text-red-700">Total pesanan tidak masuk batas metode ini.</span>
                                @endunless
                            </label>
                        @endforeach
                    </div>
                @else
                    <div class="mt-6 rounded-2xl border border-red-200 bg-red-50 p-4 text-sm font-semibold text-red-700">Belum ada metode pembayaran aktif yang dapat dipilih.</div>
                @endif

                <div class="mt-6 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm leading-6 text-amber-900">
                    <i class="fas fa-circle-info mr-2 text-secondary" aria-hidden="true"></i>
                    Setelah disimpan, link pembayaran lama tidak dipakai lagi. Jika Anda sudah sempat membayar, cek status pembayaran terlebih dahulu dari detail pesanan.
                </div>

                <button @disabled(count($paymentMethods) === 0) class="mt-6 w-full rounded-xl bg-primary px-5 py-4 font-black text-white shadow-lg shadow-primary/20 transition hover:bg-secondary disabled:cursor-not-allowed disabled:opacity-50">
                    <i class="fas fa-rotate mr-2" aria-hidden="true"></i>Ganti & Buka Pembayaran
                </button>
            </form>

            <aside class="h-fit rounded-3xl bg-gradient-to-br from-primary to-secondary p-6 text-white shadow-xl shadow-primary/20">
                <p class="text-xs font-black uppercase tracking-widest text-accent-yellow">Ringkasan</p>
                <h2 class="mt-2 break-all font-mono text-xl font-black">{{ $order->invoice_number }}</h2>
                <dl class="mt-5 space-y-3 text-sm">
                    <div class="flex justify-between gap-4"><dt class="text-blue-50">Status</dt><dd><x-status-badge :status="$order->payment_status" type="payment" /></dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-blue-50">Metode sekarang</dt><dd class="font-bold">{{ $order->gateway_payment_method ?: '-' }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-blue-50">Tagihan toko</dt><dd class="font-bold">Rp {{ number_format($order->total, 0, ',', '.') }}</dd></div>
                    @if($order->gateway_total && $order->gateway_total !== $order->total)
                        <div class="flex justify-between gap-4 border-t border-white/20 pt-3"><dt class="text-blue-50">Total saat ini</dt><dd class="font-black">Rp {{ number_format($order->gateway_total, 0, ',', '.') }}</dd></div>
                    @endif
                    @if($order->payment_expires_at)
                        <div class="flex justify-between gap-4"><dt class="text-blue-50">Berlaku hingga</dt><dd class="text-right font-semibold">{{ $order->payment_expires_at->format('d/m/Y H:i') }} WIB</dd></div>
                    @endif
                </dl>
                <a href="{{ route('orders.sync-payment', $order) }}" onclick="event.preventDefault(); document.getElementById('sync-payment-form').submit();" class="mt-5 flex items-center justify-center gap-2 rounded-xl bg-white/10 px-4 py-3 text-sm font-bold text-white ring-1 ring-white/30 hover:bg-white/20">
                    <i class="fas fa-arrows-rotate" aria-hidden="true"></i>Cek status dulu
                </a>
            </aside>
        </div>

        <form id="sync-payment-form" method="POST" action="{{ route('orders.sync-payment', $order) }}" class="hidden">
            @csrf
        </form>
    </div>
</x-layouts.app>
