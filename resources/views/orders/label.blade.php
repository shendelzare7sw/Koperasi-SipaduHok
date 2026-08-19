<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Label Pengiriman {{ $order->invoice_number }}</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}?v=2">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-100 p-4 text-slate-950 sm:p-8 print:bg-white print:p-0">
    <div class="mx-auto mb-4 flex max-w-3xl justify-between print:hidden"><a href="{{ route('admin.orders.show', $order) }}" class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-bold">Kembali</a><button onclick="window.print()" class="rounded-xl bg-primary px-4 py-2 text-sm font-bold text-white">Cetak Label</button></div>
    <main class="mx-auto max-w-3xl border-4 border-slate-950 bg-white p-6 sm:p-8 print:max-w-none">
        <header class="flex items-start justify-between gap-4 border-b-4 border-slate-950 pb-5">
            <div class="flex items-center gap-3"><img src="{{ asset('img/logo.png') }}" alt="Logo" class="h-20 w-20 object-contain"><div><h1 class="text-xl font-black">{{ $settings['legal_name'] }}</h1><p class="text-xs">{{ $settings['support_email'] }}</p></div></div>
            <div class="text-right"><p class="text-xs font-black uppercase tracking-widest">Kurir Toko</p><p class="mt-1 font-mono text-lg font-black">{{ $order->invoice_number }}</p></div>
        </header>
        <div class="mt-6 grid gap-6 sm:grid-cols-2">
            <section class="border-2 border-slate-950 p-5"><p class="text-xs font-black uppercase tracking-widest">Penerima</p><p class="mt-3 text-2xl font-black">{{ $order->buyer_name }}</p><p class="mt-1 font-bold">{{ $order->phone }}</p><p class="mt-4 whitespace-pre-line text-sm leading-6">{{ $order->delivery_address }}</p><p class="mt-4 text-sm"><strong>Siswa:</strong> {{ $order->student_name }} / {{ $order->class_name }}</p></section>
            <section class="border-2 border-slate-950 p-5"><p class="text-xs font-black uppercase tracking-widest">Informasi Paket</p><dl class="mt-3 space-y-3 text-sm"><div class="flex justify-between gap-3"><dt>Kurir</dt><dd class="font-bold">{{ $order->courier_name }}</dd></div><div class="flex justify-between gap-3"><dt>Jumlah item</dt><dd class="font-bold">{{ $order->items->sum('quantity') }}</dd></div><div class="flex justify-between gap-3"><dt>Tanggal</dt><dd class="font-bold">{{ $order->created_at->format('d/m/Y') }}</dd></div></dl><div class="mt-5 border-t-2 border-dashed border-slate-400 pt-4"><p class="text-xs font-black uppercase">Isi paket</p><ul class="mt-2 space-y-1 text-xs">@foreach($order->items as $item)<li>{{ $item->quantity }}× {{ $item->product_name }}</li>@endforeach</ul></div></section>
        </div>
        <p class="mt-6 border-t-2 border-slate-950 pt-4 text-center text-xs font-bold">Paket resmi {{ $settings['legal_name'] }} · Bukti tiba wajib diunggah admin.</p>
    </main>
</body>
</html>
