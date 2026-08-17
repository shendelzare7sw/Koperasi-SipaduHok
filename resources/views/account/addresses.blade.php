<x-layouts.app title="Alamat Tersimpan - Koperasi Sipaduhok">
    <div
        class="mx-auto max-w-5xl"
        x-data="{ adding: {{ $errors->any() && ! old('_editing_address_id') ? 'true' : 'false' }} }"
        @keydown.escape.window="adding = false"
    >
        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-extrabold uppercase tracking-widest text-secondary">Pengaturan akun</p>
                <h1 class="mt-1 text-3xl font-black text-slate-900">Alamat Tersimpan</h1>
                <p class="mt-2 text-sm text-slate-500">Kelola alamat tujuan Kurir Koperasi tanpa API ekspedisi atau ongkir eksternal.</p>
            </div>
            <button type="button" @click="adding = true" class="inline-flex items-center justify-center gap-2 rounded-xl bg-primary px-5 py-3 font-bold text-white shadow-lg shadow-primary/20 transition hover:bg-secondary">
                <i class="fas fa-plus" aria-hidden="true"></i>Tambah Alamat
            </button>
        </div>

        @include('account._navigation')

        @if($checkoutItemIds)
            <div class="mt-6 flex flex-col gap-3 rounded-2xl border border-primary/20 bg-blue-50 p-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-start gap-3 text-sm text-slate-700">
                    <i class="fas fa-cart-shopping mt-1 text-primary" aria-hidden="true"></i>
                    <p><strong>Alamat dibutuhkan untuk checkout.</strong><br>Pilih atau tambahkan alamat, lalu lanjutkan kembali ke checkout.</p>
                </div>
                @if($addresses->isNotEmpty())
                    <a href="{{ route('checkout.create', ['items' => $checkoutItemIds]) }}" class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-sm font-bold text-white hover:bg-secondary">Lanjut Checkout<i class="fas fa-arrow-right" aria-hidden="true"></i></a>
                @endif
            </div>
        @endif

        <section class="mt-6 grid gap-4 md:grid-cols-2" aria-label="Daftar alamat pembeli">
            @forelse($addresses as $address)
                @php
                    $editingThis = (int) old('_editing_address_id') === $address->id;
                @endphp
                <article x-data="{ editing: {{ $editingThis ? 'true' : 'false' }} }" class="relative overflow-hidden rounded-2xl border bg-white shadow-sm {{ $address->is_primary ? 'border-primary ring-2 ring-primary/10' : 'border-slate-200' }}">
                    @if($address->is_primary)
                        <span class="absolute right-0 top-0 rounded-bl-xl bg-primary px-3 py-1 text-[10px] font-extrabold uppercase tracking-wider text-white">Utama</span>
                    @endif

                    <div x-show="! editing" class="p-5">
                        <div class="flex items-start gap-3 pr-14">
                            <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-blue-50 text-primary"><i class="fas fa-location-dot" aria-hidden="true"></i></span>
                            <div class="min-w-0">
                                <h2 class="font-extrabold text-slate-900">{{ $address->label }}</h2>
                                <p class="mt-1 font-bold text-slate-700">{{ $address->recipient_name }}</p>
                                <p class="text-sm text-slate-500">{{ $address->phone }}</p>
                            </div>
                        </div>
                        <p class="mt-4 whitespace-pre-line text-sm leading-6 text-slate-600">{{ $address->formattedAddress() }}</p>

                        <div class="mt-5 flex flex-wrap items-center gap-2 border-t border-slate-100 pt-4">
                            <button type="button" @click="editing = true" class="rounded-xl border border-slate-300 px-3 py-2 text-xs font-bold text-primary hover:border-primary hover:bg-blue-50"><i class="fas fa-pen mr-1" aria-hidden="true"></i>Ubah</button>
                            @unless($address->is_primary)
                                <form method="POST" action="{{ route('account.addresses.primary', $address) }}" data-confirm="Alamat {{ $address->label }} akan dipakai sebagai pilihan utama saat checkout." data-confirm-title="Jadikan alamat utama?" data-confirm-button="Ya, jadikan utama">
                                    @csrf @method('PATCH')
                                    <button class="rounded-xl bg-blue-50 px-3 py-2 text-xs font-bold text-primary hover:bg-blue-100">Jadikan Utama</button>
                                </form>
                            @endunless
                            <form method="POST" action="{{ route('account.addresses.destroy', $address) }}" class="ml-auto" data-confirm="Alamat {{ $address->label }} akan dihapus permanen dari akun." data-confirm-title="Hapus alamat?" data-confirm-icon="warning" data-confirm-button="Ya, hapus">
                                @csrf @method('DELETE')
                                <button class="rounded-xl bg-red-50 px-3 py-2 text-xs font-bold text-red-600 hover:bg-red-100"><i class="fas fa-trash mr-1" aria-hidden="true"></i>Hapus</button>
                            </form>
                        </div>
                    </div>

                    <form x-cloak x-show="editing" method="POST" action="{{ route('account.addresses.update', $address) }}" class="p-5" data-confirm="Perubahan alamat akan disimpan ke akun Anda." data-confirm-title="Simpan perubahan alamat?" data-confirm-button="Ya, simpan">
                        @csrf @method('PUT')
                        <input type="hidden" name="_editing_address_id" value="{{ $address->id }}">
                        <div class="flex items-center justify-between gap-3"><h2 class="font-extrabold text-slate-900">Ubah {{ $address->label }}</h2><button type="button" @click="editing = false" class="grid h-9 w-9 place-items-center rounded-full bg-slate-100 text-slate-500" aria-label="Tutup formulir edit"><i class="fas fa-xmark"></i></button></div>
                        <div class="mt-5 grid gap-4 sm:grid-cols-2">
                            <label class="text-sm font-semibold">Label alamat<input name="label" value="{{ $editingThis ? old('label') : $address->label }}" required maxlength="50" class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3"></label>
                            <label class="text-sm font-semibold">Nama penerima<input name="recipient_name" value="{{ $editingThis ? old('recipient_name') : $address->recipient_name }}" required maxlength="255" class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3"></label>
                            <label class="text-sm font-semibold sm:col-span-2">Nomor HP<input name="phone" type="tel" inputmode="tel" value="{{ $editingThis ? old('phone') : $address->phone }}" required maxlength="20" class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3"></label>
                            <label class="text-sm font-semibold sm:col-span-2">Jalan, nomor rumah, RT/RW<textarea name="full_address" rows="3" required maxlength="1000" class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3">{{ $editingThis ? old('full_address') : $address->full_address }}</textarea></label>
                            <label class="text-sm font-semibold">Kelurahan/Desa<input name="village" value="{{ $editingThis ? old('village') : $address->village }}" required maxlength="100" class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3"></label>
                            <label class="text-sm font-semibold">Kecamatan<input name="district" value="{{ $editingThis ? old('district') : $address->district }}" required maxlength="100" class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3"></label>
                            <label class="text-sm font-semibold">Kota/Kabupaten<input name="city" value="{{ $editingThis ? old('city') : $address->city }}" required maxlength="100" class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3"></label>
                            <label class="text-sm font-semibold">Provinsi<input name="province" value="{{ $editingThis ? old('province') : $address->province }}" required maxlength="100" class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3"></label>
                            <label class="text-sm font-semibold">Kode pos<input name="postal_code" inputmode="numeric" pattern="[0-9]{5}" value="{{ $editingThis ? old('postal_code') : $address->postal_code }}" required maxlength="5" class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3"></label>
                            <label class="flex items-center gap-2 text-sm font-semibold sm:col-span-2"><input name="is_primary" type="checkbox" value="1" @checked($editingThis ? old('is_primary') : $address->is_primary) class="rounded border-slate-300"> Jadikan alamat utama</label>
                        </div>
                        <div class="mt-5 flex gap-2"><button class="rounded-xl bg-primary px-4 py-2.5 text-sm font-bold text-white hover:bg-secondary">Simpan Perubahan</button><button type="button" @click="editing = false" class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-bold">Batal</button></div>
                    </form>
                </article>
            @empty
                <div class="rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-14 text-center md:col-span-2">
                    <i class="fas fa-map-location-dot text-4xl text-slate-300" aria-hidden="true"></i>
                    <h2 class="mt-4 font-extrabold text-slate-900">Belum ada alamat pengiriman</h2>
                    <p class="mt-1 text-sm text-slate-500">Tambahkan alamat agar proses checkout lebih cepat dan aman.</p>
                    <button type="button" @click="adding = true" class="mt-5 rounded-xl bg-primary px-5 py-3 text-sm font-bold text-white hover:bg-secondary">Tambah Alamat Pertama</button>
                </div>
            @endforelse
        </section>

        <div x-cloak x-show="adding" x-transition.opacity class="fixed inset-0 z-[90] overflow-y-auto bg-slate-950/70 p-3 sm:p-6" role="dialog" aria-modal="true" aria-labelledby="add-address-title">
            <div class="flex min-h-full items-end justify-center sm:items-center">
                <form method="POST" action="{{ route('account.addresses.store') }}" @click.outside="adding = false" class="w-full max-w-2xl overflow-hidden rounded-t-3xl bg-white shadow-2xl sm:rounded-3xl" data-confirm="Alamat baru akan disimpan untuk checkout berikutnya." data-confirm-title="Simpan alamat baru?" data-confirm-button="Ya, simpan">
                    @csrf
                    <div class="flex items-center justify-between border-b border-slate-200 bg-slate-50 px-5 py-4 sm:px-6">
                        <div><p class="text-xs font-extrabold uppercase tracking-widest text-secondary">Alamat Pembeli</p><h2 id="add-address-title" class="mt-1 text-xl font-black text-slate-900">Tambah Alamat Baru</h2></div>
                        <button type="button" @click="adding = false" class="grid h-10 w-10 place-items-center rounded-full bg-white text-slate-500 shadow-sm" aria-label="Tutup formulir"><i class="fas fa-xmark"></i></button>
                    </div>
                    <div class="max-h-[75vh] overflow-y-auto p-5 sm:p-6">
                        <div class="grid gap-4 sm:grid-cols-2">
                            <label class="text-sm font-semibold">Label alamat<input name="label" value="{{ old('label') }}" placeholder="Rumah, Kantor" required maxlength="50" autofocus class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3"></label>
                            <label class="text-sm font-semibold">Nama penerima<input name="recipient_name" value="{{ old('recipient_name', auth()->user()->name) }}" required maxlength="255" class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3"></label>
                            <label class="text-sm font-semibold sm:col-span-2">Nomor HP<input name="phone" type="tel" inputmode="tel" value="{{ old('phone', auth()->user()->phone) }}" required maxlength="20" class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3"></label>
                            <label class="text-sm font-semibold sm:col-span-2">Jalan, nomor rumah, RT/RW<textarea name="full_address" rows="3" required maxlength="1000" placeholder="Contoh: Jl. Pendidikan No. 10, RT 02/RW 03" class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3">{{ old('full_address') }}</textarea></label>
                            <label class="text-sm font-semibold">Kelurahan/Desa<input name="village" value="{{ old('village') }}" required maxlength="100" class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3"></label>
                            <label class="text-sm font-semibold">Kecamatan<input name="district" value="{{ old('district') }}" required maxlength="100" class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3"></label>
                            <label class="text-sm font-semibold">Kota/Kabupaten<input name="city" value="{{ old('city') }}" required maxlength="100" class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3"></label>
                            <label class="text-sm font-semibold">Provinsi<input name="province" value="{{ old('province') }}" required maxlength="100" class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3"></label>
                            <label class="text-sm font-semibold">Kode pos<input name="postal_code" inputmode="numeric" pattern="[0-9]{5}" value="{{ old('postal_code') }}" required maxlength="5" placeholder="12345" class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3"></label>
                            <label class="flex items-center gap-2 text-sm font-semibold sm:col-span-2"><input name="is_primary" type="checkbox" value="1" @checked(old('is_primary')) class="rounded border-slate-300"> Jadikan alamat utama</label>
                        </div>
                    </div>
                    <div class="flex gap-3 border-t border-slate-200 bg-slate-50 px-5 py-4 sm:justify-end sm:px-6"><button type="button" @click="adding = false" class="flex-1 rounded-xl border border-slate-300 px-4 py-3 text-sm font-bold sm:flex-none">Batal</button><button class="flex-1 rounded-xl bg-primary px-5 py-3 text-sm font-bold text-white hover:bg-secondary sm:flex-none">Simpan Alamat</button></div>
                </form>
            </div>
        </div>
    </div>
</x-layouts.app>
