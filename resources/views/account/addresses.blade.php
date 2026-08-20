<x-layouts.app title="Alamat Tersimpan - Toko Sipaduhok">
    <div class="mx-auto max-w-5xl" x-data="{ adding: {{ $errors->any() && ! old('_editing_address_id') ? 'true' : 'false' }} }" @keydown.escape.window="adding = false">
        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div><p class="text-sm font-extrabold uppercase tracking-widest text-secondary">Pengaturan akun</p><h1 class="mt-1 text-3xl font-black text-slate-900">Alamat Tersimpan</h1><p class="mt-2 text-sm text-slate-500">Pilih wilayah secara bertingkat dan tandai titik tujuan Kurir Toko. Layanan saat ini berfokus di Tangerang dan sekitarnya.</p></div>
            <button type="button" @click="adding = true; $nextTick(() => window.dispatchEvent(new CustomEvent('address-form-visible', { detail: 'create' })))" class="inline-flex items-center justify-center gap-2 rounded-xl bg-primary px-5 py-3 font-bold text-white shadow-lg shadow-primary/20 transition hover:bg-secondary"><i class="fas fa-plus"></i>Tambah Alamat</button>
        </div>
        @include('account._navigation')

        @if($checkoutItemIds)
            <div class="mt-6 flex flex-col gap-3 rounded-2xl border border-primary/20 bg-blue-50 p-4 sm:flex-row sm:items-center sm:justify-between"><p class="text-sm text-slate-700"><i class="fas fa-cart-shopping mr-2 text-primary"></i><strong>Alamat dibutuhkan untuk checkout.</strong> Simpan alamat, kemudian lanjutkan checkout.</p>@if($addresses->isNotEmpty())<a href="{{ route('checkout.create', ['items' => $checkoutItemIds]) }}" class="rounded-xl bg-primary px-4 py-2.5 text-center text-sm font-bold text-white">Lanjut Checkout <i class="fas fa-arrow-right ml-1"></i></a>@endif</div>
        @endif

        <section class="mt-6 grid gap-4 md:grid-cols-2" aria-label="Daftar alamat pembeli">
            @forelse($addresses as $address)
                @php($editingThis = (int) old('_editing_address_id') === $address->id)
                <article x-data="{ editing: {{ $editingThis ? 'true' : 'false' }} }" class="relative overflow-hidden rounded-2xl border bg-white shadow-sm {{ $address->is_primary ? 'border-primary ring-2 ring-primary/10' : 'border-slate-200' }}">
                    @if($address->is_primary)<span class="absolute right-0 top-0 rounded-bl-xl bg-primary px-3 py-1 text-[10px] font-extrabold uppercase tracking-wider text-white">Utama</span>@endif
                    <div x-show="!editing" class="p-5">
                        <div class="flex items-start gap-3 pr-14"><span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-blue-50 text-primary"><i class="fas fa-location-dot"></i></span><div><h2 class="font-extrabold text-slate-900">{{ $address->label }}</h2><p class="mt-1 font-bold text-slate-700">{{ $address->recipient_name }}</p><p class="text-sm text-slate-500">{{ $address->phone }}</p></div></div>
                        <p class="mt-4 whitespace-pre-line text-sm leading-6 text-slate-600">{{ $address->formattedAddress() }}</p>
                        @if($address->mapsUrl())<a href="{{ $address->mapsUrl() }}" target="_blank" rel="noopener noreferrer" class="mt-3 inline-flex items-center gap-2 text-xs font-bold text-primary hover:underline"><i class="fas fa-map-location-dot"></i>Buka titik lokasi</a>@else<p class="mt-3 text-xs font-semibold text-amber-700"><i class="fas fa-triangle-exclamation mr-1"></i>Alamat lama ini belum memiliki titik peta.</p>@endif
                        <div class="mt-5 flex flex-wrap items-center gap-2 border-t border-slate-100 pt-4">
                            <button type="button" @click="editing = true; $nextTick(() => window.dispatchEvent(new CustomEvent('address-form-visible', { detail: 'edit-{{ $address->id }}' })))" class="rounded-xl border border-slate-300 px-3 py-2 text-xs font-bold text-primary hover:bg-blue-50"><i class="fas fa-pen mr-1"></i>Ubah</button>
                            @unless($address->is_primary)<form method="POST" action="{{ route('account.addresses.primary', $address) }}" data-confirm="Alamat {{ $address->label }} akan dipakai saat checkout." data-confirm-title="Jadikan alamat utama?">@csrf @method('PATCH')<button class="rounded-xl bg-blue-50 px-3 py-2 text-xs font-bold text-primary">Jadikan Utama</button></form>@endunless
                            <form method="POST" action="{{ route('account.addresses.destroy', $address) }}" class="ml-auto" data-confirm="Alamat {{ $address->label }} akan dihapus permanen." data-confirm-title="Hapus alamat?" data-confirm-icon="warning">@csrf @method('DELETE')<button class="rounded-xl bg-red-50 px-3 py-2 text-xs font-bold text-red-600"><i class="fas fa-trash mr-1"></i>Hapus</button></form>
                        </div>
                    </div>
                    <form x-cloak x-show="editing" method="POST" action="{{ route('account.addresses.update', $address) }}" class="p-5" data-confirm="Perubahan alamat akan disimpan." data-confirm-title="Simpan perubahan alamat?">
                        @csrf @method('PUT')<input type="hidden" name="_editing_address_id" value="{{ $address->id }}">
                        <div class="mb-5 flex items-center justify-between"><h2 class="font-extrabold text-slate-900">Ubah {{ $address->label }}</h2><button type="button" @click="editing = false" class="grid h-9 w-9 place-items-center rounded-full bg-slate-100 text-slate-500"><i class="fas fa-xmark"></i></button></div>
                        @include('account._address-form-fields', ['address' => $address, 'useOld' => $editingThis, 'formKey' => 'edit-'.$address->id])
                        <div class="mt-5 flex gap-2"><button class="rounded-xl bg-primary px-4 py-2.5 text-sm font-bold text-white">Simpan Perubahan</button><button type="button" @click="editing = false" class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-bold">Batal</button></div>
                    </form>
                </article>
            @empty
                <div class="rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-14 text-center md:col-span-2"><i class="fas fa-map-location-dot text-4xl text-slate-300"></i><h2 class="mt-4 font-extrabold">Belum ada alamat pengiriman</h2><p class="mt-1 text-sm text-slate-500">Tambahkan alamat beserta titik peta untuk mempermudah kurir.</p><button type="button" @click="adding = true; $nextTick(() => window.dispatchEvent(new CustomEvent('address-form-visible', { detail: 'create' })))" class="mt-5 rounded-xl bg-primary px-5 py-3 text-sm font-bold text-white">Tambah Alamat Pertama</button></div>
            @endforelse
        </section>

        <div x-cloak x-show="adding" x-transition.opacity class="fixed inset-0 z-[90] overflow-y-auto bg-slate-950/70 p-3 sm:p-6" role="dialog" aria-modal="true">
            <div class="flex min-h-full items-end justify-center sm:items-center">
                <form method="POST" action="{{ route('account.addresses.store') }}" class="w-full max-w-3xl overflow-hidden rounded-t-3xl bg-white shadow-2xl sm:rounded-3xl" data-confirm="Alamat baru akan disimpan untuk checkout berikutnya." data-confirm-title="Simpan alamat baru?">
                    @csrf
                    <div class="flex items-center justify-between border-b border-slate-200 bg-slate-50 px-5 py-4"><div><p class="text-xs font-extrabold uppercase tracking-widest text-secondary">Alamat Pembeli</p><h2 class="mt-1 text-xl font-black">Tambah Alamat Baru</h2></div><button type="button" @click="adding = false" class="grid h-10 w-10 place-items-center rounded-full bg-white text-slate-500 shadow-sm"><i class="fas fa-xmark"></i></button></div>
                    <div class="max-h-[75vh] overflow-y-auto p-5 sm:p-6">@include('account._address-form-fields', ['address' => null, 'useOld' => $errors->any() && !old('_editing_address_id'), 'formKey' => 'create'])</div>
                    <div class="flex gap-3 border-t border-slate-200 bg-slate-50 px-5 py-4 sm:justify-end"><button type="button" @click="adding = false" class="rounded-xl border border-slate-300 px-5 py-3 text-sm font-bold">Batal</button><button class="rounded-xl bg-primary px-5 py-3 text-sm font-bold text-white">Simpan Alamat</button></div>
                </form>
            </div>
        </div>
    </div>
</x-layouts.app>
