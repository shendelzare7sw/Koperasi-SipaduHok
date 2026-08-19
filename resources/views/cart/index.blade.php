<x-layouts.app title="Keranjang - Toko Sipaduhok">
    <div class="mb-6 flex items-end justify-between gap-4">
        <div><h1 class="text-3xl font-black text-slate-900">Keranjang</h1><p class="text-slate-500">Periksa jumlah sebelum checkout.</p></div>
        <a href="{{ route('catalog.index') }}" class="text-sm font-bold text-primary hover:text-secondary">Lanjut belanja</a>
    </div>

    <div class="grid gap-6 lg:grid-cols-[1fr_320px]">
        <div class="space-y-3">
            @forelse($items as $item)
                <article class="flex flex-col gap-4 rounded-2xl border border-slate-200 bg-white p-4 sm:flex-row sm:items-center">
                    <label class="flex h-10 w-10 shrink-0 cursor-pointer items-center justify-center rounded-xl bg-slate-100" title="Pilih untuk checkout">
                        <input form="checkout-selected" type="checkbox" name="items[]" value="{{ $item->id }}" checked class="h-5 w-5 rounded border-slate-300 text-primary focus:ring-primary">
                    </label>
                    <div class="min-w-0 flex-1">
                        <h2 class="font-bold text-slate-900">{{ $item->product->name }}</h2>
                        <p class="text-sm text-slate-500">Rp {{ number_format($item->product->price, 0, ',', '.') }} / item</p>
                    </div>
                    <form method="POST" action="{{ route('cart.update', $item) }}" class="flex items-center gap-2" data-confirm="Jumlah {{ $item->product->name }} akan diperbarui." data-confirm-title="Perbarui jumlah produk?" data-confirm-button="Ya, perbarui">@csrf @method('PATCH')
                        <input name="quantity" type="number" min="1" max="{{ $item->product->stock }}" value="{{ $item->quantity }}" class="w-20 rounded-lg border border-slate-300 px-3 py-2">
                        <button class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-bold">Update</button>
                    </form>
                    <p class="w-32 text-right font-black">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</p>
                    <form method="POST" action="{{ route('cart.destroy', $item) }}" data-confirm="{{ $item->product->name }} akan dihapus dari keranjang." data-confirm-title="Hapus dari keranjang?" data-confirm-icon="warning" data-confirm-button="Ya, hapus">@csrf @method('DELETE')
                        <button class="text-sm font-bold text-red-600">Hapus</button>
                    </form>
                </article>
            @empty
                <div class="rounded-2xl border border-dashed border-slate-300 bg-white p-10 text-center text-slate-500">Keranjang masih kosong.</div>
            @endforelse
        </div>

        <aside class="h-fit rounded-2xl bg-gradient-to-br from-primary to-secondary p-6 text-white">
            <p class="text-sm text-blue-50">Subtotal produk</p>
            <p class="mt-2 text-2xl font-black">Rp {{ number_format($total, 0, ',', '.') }}</p>
            <p class="mt-3 text-xs leading-5 text-slate-400">Tarif Kurir Toko ditambahkan saat checkout.</p>
            @if($items->isNotEmpty())
                <form id="checkout-selected" method="GET" action="{{ route('checkout.create') }}" class="mt-5">
                    <button class="w-full rounded-xl bg-white px-4 py-3 text-center font-bold text-primary transition hover:bg-accent-yellow hover:text-slate-900">Checkout Produk Dipilih</button>
                </form>
                <p class="mt-2 text-center text-xs text-slate-400">Hilangkan centang untuk menyimpan produk di keranjang.</p>
            @endif
        </aside>
    </div>
</x-layouts.app>
