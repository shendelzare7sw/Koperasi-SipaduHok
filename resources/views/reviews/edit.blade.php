<x-layouts.app title="Ulasan Produk - Koperasi Sipaduhok">
    <div class="mx-auto max-w-2xl">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
            <p class="text-sm font-extrabold uppercase tracking-widest text-secondary">Pembelian terverifikasi</p>
            <h1 class="mt-2 text-2xl font-black text-slate-900">Ulas {{ $orderItem->product_name }}</h1>
            <p class="mt-2 text-sm text-slate-500">Ulasan hanya dapat diberikan untuk produk dari pesanan yang sudah selesai.</p>

            <form x-data="{ rating: {{ old('rating', $orderItem->review?->rating ?? 5) }} }" method="POST" action="{{ route('reviews.store', $orderItem) }}" class="mt-7" data-confirm="Ulasan dan penilaian Anda akan tampil pada detail produk." data-confirm-title="Simpan ulasan produk?" data-confirm-button="Ya, simpan ulasan">
                @csrf
                <input type="hidden" name="rating" :value="rating">
                <fieldset>
                    <legend class="text-sm font-bold text-slate-700">Penilaian</legend>
                    <div class="mt-3 flex gap-2">
                        @for($star = 1; $star <= 5; $star++)
                            <button type="button" @click="rating = {{ $star }}" class="text-3xl transition hover:scale-110" :class="rating >= {{ $star }} ? 'text-accent-yellow' : 'text-slate-200'" aria-label="Beri {{ $star }} bintang"><i class="fas fa-star"></i></button>
                        @endfor
                    </div>
                    <p class="mt-2 text-xs font-semibold text-slate-500"><span x-text="rating"></span> dari 5 bintang</p>
                </fieldset>
                <label class="mt-6 block text-sm font-bold text-slate-700">Ceritakan pengalaman Anda
                    <textarea name="comment" rows="6" maxlength="1500" placeholder="Kualitas produk, kesesuaian deskripsi, atau hal lain yang membantu pembeli." class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20">{{ old('comment', $orderItem->review?->comment) }}</textarea>
                </label>
                <button class="mt-6 inline-flex w-full items-center justify-center gap-2 rounded-xl bg-primary px-5 py-3 font-bold text-white hover:bg-secondary"><i class="fas fa-star"></i>{{ $orderItem->review ? 'Perbarui Ulasan' : 'Kirim Ulasan' }}</button>
            </form>
        </div>
    </div>
</x-layouts.app>
