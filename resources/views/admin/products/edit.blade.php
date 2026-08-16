<x-layouts.app title="Edit Produk">
    <div class="mx-auto max-w-3xl">
        <h1 class="text-3xl font-black">Edit Produk</h1>

        @if($product->images->isNotEmpty())
            <section class="mt-6 rounded-2xl border border-slate-200 bg-white p-5">
                <div class="flex items-center justify-between gap-4">
                    <div><h2 class="font-extrabold text-slate-900">Galeri Produk</h2><p class="text-xs text-slate-500">Maksimal lima foto per produk.</p></div>
                    <span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-bold text-primary">{{ $product->images->count() }}/5 foto</span>
                </div>
                <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-3">
                    @foreach($product->images as $image)
                        <div class="relative overflow-hidden rounded-xl border border-slate-200 bg-slate-100">
                            <x-image-lightbox :src="Storage::disk('public')->url($image->image_path)" alt="Foto {{ $product->name }}" image-class="aspect-square w-full object-cover" />
                            @if($image->is_primary)<span class="absolute left-2 top-2 rounded-full bg-primary px-2 py-1 text-[9px] font-extrabold uppercase text-white">Utama</span>@endif
                            <form method="POST" action="{{ route('admin.products.images.destroy', [$product, $image]) }}" class="absolute bottom-2 right-2" data-confirm="Foto ini akan dihapus permanen dari galeri produk." data-confirm-title="Hapus foto produk?" data-confirm-icon="warning" data-confirm-button="Ya, hapus">@csrf @method('DELETE')<button class="grid h-9 w-9 place-items-center rounded-full bg-white text-red-600 shadow-lg" aria-label="Hapus foto"><i class="fas fa-trash"></i></button></form>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif

        <form method="POST" action="{{ route('admin.products.update', $product) }}" enctype="multipart/form-data" class="mt-6 rounded-2xl border border-slate-200 bg-white p-6" data-confirm="Perubahan produk akan tampil pada katalog koperasi." data-confirm-title="Simpan perubahan produk?" data-confirm-button="Ya, simpan">
            @include('admin.products._form')
        </form>
    </div>
</x-layouts.app>
