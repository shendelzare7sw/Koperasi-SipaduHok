<x-layouts.app title="Keranjang - Toko Sipaduhok">
    <div class="mb-6 flex items-end justify-between gap-4">
        <div><h1 class="text-3xl font-black text-slate-900">Keranjang</h1><p class="text-slate-500">Periksa jumlah sebelum checkout.</p></div>
        <a href="{{ route('catalog.index') }}" class="text-sm font-bold text-primary hover:text-secondary">Lanjut belanja</a>
    </div>

    <div x-data="{ selectedCount: {{ $items->count() }} }" class="grid gap-6 lg:grid-cols-[1fr_320px]">
        <div class="space-y-3">
            @forelse($items as $item)
                <article class="flex flex-col gap-4 rounded-2xl border border-slate-200 bg-white p-4 sm:flex-row sm:items-center">
                    <label class="flex h-10 w-10 shrink-0 cursor-pointer items-center justify-center rounded-xl bg-slate-100" title="Pilih untuk checkout">
                        <input form="checkout-selected" type="checkbox" name="items[]" value="{{ $item->id }}" checked @change="selectedCount += $event.target.checked ? 1 : -1" class="h-5 w-5 rounded border-slate-300 text-primary focus:ring-primary">
                    </label>
                    <div class="min-w-0 flex-1">
                        <h2 class="font-bold text-slate-900">{{ $item->product->name }}</h2>
                        <p class="text-sm text-slate-500">Rp {{ number_format($item->product->price, 0, ',', '.') }} / item</p>
                    </div>
                    <div class="inline-flex items-center overflow-hidden rounded-xl border border-slate-300 bg-white" aria-label="Atur jumlah {{ $item->product->name }}">
                        <form method="POST" action="{{ route('cart.update', $item) }}">@csrf @method('PATCH')
                            <input type="hidden" name="quantity" value="{{ max(1, $item->quantity - 1) }}">
                            <button data-cart-decrement @disabled($item->quantity <= 1) class="grid h-10 w-10 place-items-center text-slate-600 transition hover:bg-slate-100 hover:text-primary disabled:cursor-not-allowed disabled:text-slate-300" aria-label="Kurangi jumlah {{ $item->product->name }}"><i class="fas fa-minus text-xs" aria-hidden="true"></i></button>
                        </form>
                        <span class="grid h-10 min-w-11 place-items-center border-x border-slate-200 px-2 text-sm font-black text-slate-900" aria-label="Jumlah saat ini">{{ $item->quantity }}</span>
                        <form method="POST" action="{{ route('cart.update', $item) }}">@csrf @method('PATCH')
                            <input type="hidden" name="quantity" value="{{ $item->quantity + 1 }}">
                            <button data-cart-increment @disabled($item->quantity >= $item->product->stock) class="grid h-10 w-10 place-items-center text-slate-600 transition hover:bg-slate-100 hover:text-primary disabled:cursor-not-allowed disabled:text-slate-300" aria-label="Tambah jumlah {{ $item->product->name }}"><i class="fas fa-plus text-xs" aria-hidden="true"></i></button>
                        </form>
                    </div>
                    <p class="w-32 text-right font-black">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</p>
                    <form method="POST" action="{{ route('cart.destroy', $item) }}">@csrf @method('DELETE')
                        <button class="grid h-10 w-10 place-items-center rounded-xl text-red-600 transition hover:bg-red-50" aria-label="Hapus {{ $item->product->name }} dari keranjang"><i class="fas fa-trash-can" aria-hidden="true"></i></button>
                    </form>
                </article>
            @empty
                <div class="rounded-2xl border border-dashed border-slate-300 bg-white p-10 text-center text-slate-500">Keranjang masih kosong.</div>
            @endforelse
        </div>

        <aside class="h-fit rounded-2xl bg-gradient-to-br from-primary to-secondary p-6 text-white">
            <p class="text-sm text-blue-50">Subtotal produk</p>
            <p class="mt-2 text-2xl font-black">Rp {{ number_format($total, 0, ',', '.') }}</p>
            <p class="mt-3 text-xs leading-5 text-white/85">Tarif Kurir Toko ditambahkan saat checkout.</p>
            @if($items->isNotEmpty())
                <form id="checkout-selected" method="GET" action="{{ route('checkout.create') }}" class="mt-5">
                    <input type="hidden" name="selected" value="1">
                    <button :disabled="selectedCount < 1" class="w-full rounded-xl bg-white px-4 py-3 text-center font-bold text-primary transition hover:bg-accent-yellow hover:text-slate-900 disabled:cursor-not-allowed disabled:opacity-50">Checkout <span x-text="selectedCount"></span> Produk</button>
                </form>
                <p class="mt-2 text-center text-xs text-white/85">Hilangkan centang untuk menyimpan produk di keranjang.</p>
            @endif
        </aside>
    </div>
</x-layouts.app>
