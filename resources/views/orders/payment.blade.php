<x-layouts.app title="Pembayaran {{ $order->invoice_number }}">
    <div class="mx-auto max-w-2xl">
        <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-xl shadow-slate-200/60">
            <div class="bg-gradient-to-br from-primary to-secondary px-6 py-8 text-center text-white sm:px-10">
                <img src="{{ asset('img/logo.png') }}?v={{ filemtime(public_path('img/logo.png')) }}" alt="Logo Sipaduhok" class="mx-auto h-20 w-20 object-contain drop-shadow-lg">
                <p class="mt-4 text-sm font-bold uppercase tracking-widest text-accent-yellow">Pesanan berhasil dibuat</p>
                <h1 class="mt-2 text-2xl font-black">Selesaikan Pembayaran</h1>
                <p class="mt-2 text-sm text-blue-50">Pembayaran aman diproses melalui halaman resmi Paywuz.</p>
            </div>

            <div class="p-6 sm:p-10">
                <dl class="space-y-4 rounded-2xl bg-slate-50 p-5 text-sm">
                    <div class="flex justify-between gap-4"><dt class="text-slate-500">Nomor invoice</dt><dd class="break-all text-right font-mono font-bold text-slate-900">{{ $order->invoice_number }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-slate-500">Kanal</dt><dd class="font-bold">{{ $order->gateway_payment_method }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-slate-500">Tagihan toko</dt><dd class="font-bold">Rp {{ number_format($order->total, 0, ',', '.') }}</dd></div>
                    @if($order->gateway_total && $order->gateway_total !== $order->total)
                        <div class="flex justify-between gap-4 border-t border-slate-200 pt-4 text-lg"><dt class="font-black">Total via Paywuz</dt><dd class="font-black text-primary">Rp {{ number_format($order->gateway_total, 0, ',', '.') }}</dd></div>
                    @endif
                    @if($order->payment_expires_at)
                        <div class="flex justify-between gap-4"><dt class="text-slate-500">Berlaku hingga</dt><dd class="text-right font-semibold text-slate-700">{{ $order->payment_expires_at->format('d/m/Y H:i') }} WIB</dd></div>
                    @endif
                </dl>

                @if($order->payment_url)
                    <a href="{{ $order->payment_url }}" rel="noreferrer" class="mt-6 block w-full rounded-xl bg-primary px-5 py-4 text-center font-black text-white shadow-lg shadow-primary/20 transition hover:bg-secondary"><i class="fas fa-arrow-up-right-from-square mr-2" aria-hidden="true"></i>Buka Pembayaran Paywuz</a>
                @else
                    <div class="mt-6 rounded-xl border border-red-200 bg-red-50 p-4 text-center text-sm font-semibold text-red-700">URL pembayaran belum tersedia. Kembali ke detail pesanan untuk mencoba lagi.</div>
                @endif
                <a href="{{ route('orders.show', $order) }}" class="mt-4 block text-center text-sm font-semibold text-slate-500 hover:text-slate-900">Bayar nanti dari detail pesanan</a>
                <p class="mt-5 text-center text-xs leading-5 text-slate-400">Pastikan domain halaman pembayaran adalah Paywuz. Status pesanan diperbarui otomatis melalui webhook aman.</p>
            </div>
        </section>
    </div>
</x-layouts.app>
