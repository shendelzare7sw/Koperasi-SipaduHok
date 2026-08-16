<x-layouts.app title="{{ $product->name }} - Koperasi Sipaduhok">
    <div class="grid gap-8 lg:grid-cols-2">
        <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white">
            @if($product->image_path)
                <img src="{{ Storage::disk('public')->url($product->image_path) }}" alt="{{ $product->name }}" class="aspect-square w-full object-cover">
            @else
                <div class="grid aspect-square place-items-center bg-slate-100 font-bold text-slate-400">Foto produk belum tersedia</div>
            @endif
        </div>
        <div class="lg:py-8">
            <p class="font-bold uppercase tracking-wide text-orange-600">{{ App\Models\Product::CATEGORIES[$product->category] ?? $product->category }}</p>
            <h1 class="mt-2 text-3xl font-black text-slate-900 sm:text-4xl">{{ $product->name }}</h1>
            <p class="mt-4 text-2xl font-black">Rp {{ number_format($product->price, 0, ',', '.') }}</p>
            <p class="mt-2 text-sm text-slate-500">Stok tersedia: {{ $product->stock }}</p>
            <div class="mt-6 whitespace-pre-line leading-7 text-slate-600">{{ $product->description ?: 'Belum ada deskripsi.' }}</div>
            @auth
                @unless(auth()->user()->isAdmin())
                    <form method="POST" action="{{ route('cart.store', $product) }}" class="mt-8 flex gap-3">@csrf
                        <input name="quantity" type="number" min="1" max="{{ $product->stock }}" value="1" class="w-24 rounded-xl border border-slate-300 px-4 py-3">
                        <button @disabled($product->stock < 1) class="flex-1 rounded-xl bg-orange-500 px-5 py-3 font-bold text-slate-950 disabled:cursor-not-allowed disabled:opacity-50">Tambah ke Keranjang</button>
                    </form>
                @endunless
            @else
                <a href="{{ route('login') }}" class="mt-8 block rounded-xl bg-orange-500 px-5 py-3 text-center font-bold text-slate-950">Masuk untuk Belanja</a>
            @endauth
        </div>
    </div>
</x-layouts.app>
