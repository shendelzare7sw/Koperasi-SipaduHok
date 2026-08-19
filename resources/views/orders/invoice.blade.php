<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Invoice {{ $order->invoice_number }}</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}?v=2" sizes="any">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}?v=2">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-100 p-4 text-slate-900 sm:p-8 print:bg-white print:p-0">
    @php
        $backUrl = auth()->user()->isAdmin() ? route('admin.orders.show', $order) : route('orders.show', $order);
        $paymentLabel = match($order->payment_status->value) {
            'paid' => 'LUNAS', 'pending' => 'MENUNGGU PEMBAYARAN', 'failed' => 'GAGAL', 'expired' => 'KEDALUWARSA', default => 'BELUM DIBAYAR'
        };
    @endphp
    <div class="mx-auto mb-4 flex max-w-4xl justify-between print:hidden">
        <a href="{{ $backUrl }}" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-bold">Kembali</a>
        <button onclick="window.print()" class="rounded-lg bg-primary px-4 py-2 text-sm font-bold text-white">Cetak Invoice</button>
    </div>
    <main class="mx-auto max-w-4xl rounded-2xl bg-white p-6 shadow-sm sm:p-10 print:max-w-none print:rounded-none print:p-0 print:shadow-none">
        <header class="flex flex-col gap-5 border-b-4 border-primary pb-6 sm:flex-row sm:items-start sm:justify-between">
            <div class="flex items-center gap-3"><img src="{{ asset('img/logo.png') }}" alt="Logo Sipaduhok" class="h-20 w-20 object-contain"><div><h1 class="text-xl font-black">Toko Sipaduhok</h1><p class="text-sm text-slate-500">toko.sipaduhok.id</p></div></div>
            <div class="sm:text-right"><p class="text-xs font-bold uppercase tracking-widest text-primary">Invoice</p><p class="mt-1 font-mono text-lg font-black">{{ $order->invoice_number }}</p><p class="text-sm text-slate-500">{{ $order->created_at->format('d/m/Y H:i') }} WIB</p></div>
        </header>

        <section class="mt-6 grid gap-4 sm:grid-cols-2">
            <div class="rounded-xl bg-slate-50 p-4"><p class="text-xs font-bold uppercase text-slate-400">Ditagihkan kepada</p><p class="mt-2 font-bold">{{ $order->buyer_name }}</p><p class="text-sm text-slate-600">Siswa: {{ $order->student_name }} · {{ $order->class_name }}</p><p class="text-sm text-slate-600">{{ $order->phone }}</p></div>
            <div class="rounded-xl bg-slate-50 p-4"><p class="text-xs font-bold uppercase text-slate-400">Pengantaran</p><p class="mt-2 font-bold">{{ $order->courier_name }}</p><p class="mt-1 whitespace-pre-line text-sm text-slate-600">{{ $order->delivery_address }}</p></div>
        </section>

        <div class="mt-6 overflow-x-auto">
            <table class="w-full min-w-[560px] text-sm">
                <thead><tr class="border-y border-slate-200 bg-slate-50 text-left text-xs uppercase text-slate-500"><th class="px-3 py-3">Produk</th><th class="px-3 py-3 text-center">Qty</th><th class="px-3 py-3 text-right">Harga</th><th class="px-3 py-3 text-right">Subtotal</th></tr></thead>
                <tbody class="divide-y divide-slate-100">@foreach($order->items as $item)<tr><td class="px-3 py-3 font-semibold">{{ $item->product_name }}</td><td class="px-3 py-3 text-center">{{ $item->quantity }}</td><td class="px-3 py-3 text-right">Rp {{ number_format($item->unit_price, 0, ',', '.') }}</td><td class="px-3 py-3 text-right font-bold">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td></tr>@endforeach</tbody>
            </table>
        </div>

        <section class="mt-6 ml-auto max-w-sm space-y-2 text-sm"><div class="flex justify-between"><span>Subtotal Produk</span><span>Rp {{ number_format($order->subtotal, 0, ',', '.') }}</span></div><div class="flex justify-between"><span>Tarif Kurir Toko</span><span>Rp {{ number_format($order->shipping_cost, 0, ',', '.') }}</span></div><div class="flex justify-between border-t-2 border-slate-200 pt-3 text-lg font-black"><span>Total Tagihan</span><span>Rp {{ number_format($order->total, 0, ',', '.') }}</span></div></section>

        <section class="mt-8 flex flex-col gap-3 rounded-xl border border-slate-200 p-4 text-sm sm:flex-row sm:items-center sm:justify-between"><div><p class="text-slate-500">Metode Pembayaran</p><p class="font-bold">{{ $order->payment_method->label() }}</p></div><div><p class="text-slate-500">Status Pembayaran</p><p class="font-black">{{ $paymentLabel }}</p></div><div><p class="text-slate-500">Status Pesanan</p><p class="font-bold">{{ $order->statusLabel() }}</p></div></section>
        <p class="mt-8 text-center text-xs text-slate-400">Invoice dibuat otomatis oleh sistem Toko Sipaduhok. Simpan dokumen ini sebagai bukti transaksi.</p>
    </main>
</body>
</html>
