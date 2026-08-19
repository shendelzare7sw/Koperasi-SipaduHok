<x-layouts.app title="Pembayaran {{ $order->invoice_number }}">
    @push('head')
        <script src="{{ $snapScriptUrl }}" data-client-key="{{ $midtransClientKey }}"></script>
    @endpush

    <div class="mx-auto max-w-2xl">
        <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-xl shadow-slate-200/60">
            <div class="bg-gradient-to-br from-primary to-secondary px-6 py-8 text-center text-white sm:px-10">
                <img src="{{ asset('img/logo.png') }}" alt="Logo Sipaduhok" class="mx-auto h-20 w-20 object-contain drop-shadow-lg">
                <p class="mt-4 text-sm font-bold uppercase tracking-widest text-accent-yellow">Pesanan berhasil dibuat</p>
                <h1 class="mt-2 text-2xl font-black">Selesaikan Pembayaran</h1>
                <p class="mt-2 text-sm text-blue-50">Pembayaran diterima langsung oleh Toko Sipaduhok melalui Midtrans.</p>
            </div>

            <div class="p-6 sm:p-10">
                <dl class="space-y-4 rounded-2xl bg-slate-50 p-5 text-sm">
                    <div class="flex justify-between gap-4"><dt class="text-slate-500">Nomor invoice</dt><dd class="break-all text-right font-mono font-bold text-slate-900">{{ $order->invoice_number }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-slate-500">Metode</dt><dd class="font-bold">{{ $order->payment_method->label() }}</dd></div>
                    <div class="flex justify-between gap-4 border-t border-slate-200 pt-4 text-lg"><dt class="font-black">Total tagihan</dt><dd class="font-black text-primary">Rp {{ number_format($order->total, 0, ',', '.') }}</dd></div>
                </dl>

                <button id="pay-button" type="button" class="mt-6 w-full rounded-xl bg-primary px-5 py-4 font-black text-white shadow-lg shadow-primary/20 transition hover:bg-secondary">Bayar Sekarang</button>
                <a href="{{ route('orders.show', $order) }}" class="mt-4 block text-center text-sm font-semibold text-slate-500 hover:text-slate-900">Bayar nanti dari detail pesanan</a>
                <p class="mt-5 text-center text-xs leading-5 text-slate-400">Jangan menutup halaman saat proses pembayaran sedang dikonfirmasi.</p>
            </div>
        </section>
    </div>

    @push('scripts')
        <script>
            document.getElementById('pay-button')?.addEventListener('click', function () {
                window.snap.pay(@js($order->payment_token), {
                    onSuccess: () => window.location.href = @js(route('orders.show', $order)).concat('?payment=success'),
                    onPending: () => window.location.href = @js(route('orders.show', $order)).concat('?payment=pending'),
                    onError: () => window.location.href = @js(route('orders.show', $order)).concat('?payment=error'),
                });
            });
        </script>
    @endpush
</x-layouts.app>
