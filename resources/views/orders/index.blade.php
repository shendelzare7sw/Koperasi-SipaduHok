<x-layouts.app title="Riwayat Pesanan - Toko Sipaduhok">
    <h1 class="text-3xl font-black text-slate-900">Riwayat Pesanan</h1>
    <div class="mt-6 space-y-4">
        @forelse($orders as $order)
            <a href="{{ route('orders.show', $order) }}" class="block rounded-2xl border border-slate-200 bg-white p-5 shadow-sm hover:border-primary">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div><p class="font-mono text-sm font-bold text-slate-900">{{ $order->invoice_number }}</p><p class="text-sm text-slate-500">{{ $order->created_at->format('d/m/Y H:i') }} · {{ $order->student_name }} / {{ $order->class_name }}</p></div>
                    <div class="sm:text-right"><span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold">{{ $order->statusLabel() }}</span><p class="mt-2 font-black">Rp {{ number_format($order->total, 0, ',', '.') }}</p></div>
                </div>
            </a>
        @empty
            <div class="rounded-2xl border border-dashed border-slate-300 bg-white p-10 text-center text-slate-500">Belum ada pesanan.</div>
        @endforelse
    </div>
    <div class="mt-8">{{ $orders->links() }}</div>
</x-layouts.app>
