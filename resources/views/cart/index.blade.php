<x-layouts.app title="Keranjang - Koperasi Sipaduhok">
    <div class="mb-6 flex items-end justify-between gap-4">
        <div><h1 class="text-3xl font-black text-slate-900">Keranjang</h1><p class="text-slate-500">Periksa jumlah sebelum checkout.</p></div>
        <a href="{{ route('catalog.index') }}" class="text-sm font-bold text-orange-600">Lanjut belanja</a>
    </div>

    <div class="grid gap-6 lg:grid-cols-[1fr_320px]">
        <div class="space-y-3">
            @forelse($items as $item)
                <article class="flex flex-col gap-4 rounded-2xl border border-slate-200 bg-white p-4 sm:flex-row sm:items-center">
                    <div class="min-w-0 flex-1">
                        <h2 class="font-bold text-slate-900">{{ $item->product->name }}</h2>
                        <p class="text-sm text-slate-500">Rp {{ number_format($item->product->price, 0, ',', '.') }} / item</p>
                    </div>
                    <form method="POST" action="{{ route('cart.update', $item) }}" class="flex items-center gap-2">@csrf @method('PATCH')
                        <input name="quantity" type="number" min="1" max="{{ $item->product->stock }}" value="{{ $item->quantity }}" class="w-20 rounded-lg border border-slate-300 px-3 py-2">
                        <button class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-bold">Update</button>
                    </form>
                    <p class="w-32 text-right font-black">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</p>
                    <form method="POST" action="{{ route('cart.destroy', $item) }}">@csrf @method('DELETE')
                        <button class="text-sm font-bold text-red-600">Hapus</button>
                    </form>
                </article>
            @empty
                <div class="rounded-2xl border border-dashed border-slate-300 bg-white p-10 text-center text-slate-500">Keranjang masih kosong.</div>
            @endforelse
        </div>

        <aside class="h-fit rounded-2xl bg-slate-900 p-6 text-white">
            <p class="text-sm text-slate-300">Subtotal produk</p>
            <p class="mt-2 text-2xl font-black">Rp {{ number_format($total, 0, ',', '.') }}</p>
            <p class="mt-3 text-xs leading-5 text-slate-400">Tarif Kurir Koperasi ditambahkan saat checkout.</p>
            @if($items->isNotEmpty())<a href="{{ route('checkout.create') }}" class="mt-5 block rounded-xl bg-orange-500 px-4 py-3 text-center font-bold text-slate-950">Lanjut Checkout</a>@endif
        </aside>
    </div>
</x-layouts.app>
