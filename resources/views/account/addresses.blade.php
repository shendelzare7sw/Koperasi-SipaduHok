<x-layouts.app title="Alamat Tersimpan - Koperasi Sipaduhok">
    <div class="mx-auto max-w-5xl">
        <div class="mb-6">
            <p class="text-sm font-extrabold uppercase tracking-widest text-secondary">Pengaturan akun</p>
            <h1 class="mt-1 text-3xl font-black text-slate-900">Alamat Tersimpan</h1>
            <p class="mt-2 text-sm text-slate-500">Simpan alamat tanpa API pengiriman; checkout tetap memakai satu Kurir Koperasi.</p>
        </div>

        @include('account._navigation')

        <div class="mt-6 grid gap-6 lg:grid-cols-[minmax(0,1fr)_360px]">
            <section class="space-y-4">
                @forelse($addresses as $address)
                    <article x-data="{ editing: false }" class="rounded-2xl border bg-white p-5 shadow-sm {{ $address->is_primary ? 'border-primary/40 ring-2 ring-primary/10' : 'border-slate-200' }}">
                        <div x-show="! editing">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h2 class="font-extrabold text-slate-900">{{ $address->label }}</h2>
                                        @if($address->is_primary)<span class="rounded-full bg-blue-50 px-2.5 py-1 text-[10px] font-extrabold uppercase tracking-wider text-primary">Utama</span>@endif
                                    </div>
                                    <p class="mt-3 font-bold text-slate-800">{{ $address->recipient_name }}</p>
                                    <p class="mt-1 text-sm text-slate-500">{{ $address->phone }}</p>
                                    <p class="mt-2 whitespace-pre-line text-sm leading-6 text-slate-600">{{ $address->full_address }}</p>
                                </div>
                                <button type="button" @click="editing = true" class="rounded-xl border border-slate-200 px-3 py-2 text-xs font-bold text-primary hover:border-primary">Edit</button>
                            </div>
                            <div class="mt-5 flex flex-wrap gap-2 border-t border-slate-100 pt-4">
                                @unless($address->is_primary)
                                    <form method="POST" action="{{ route('account.addresses.primary', $address) }}" data-confirm="Alamat {{ $address->label }} akan dipakai sebagai pilihan utama saat checkout." data-confirm-title="Jadikan alamat utama?" data-confirm-button="Ya, jadikan utama">@csrf @method('PATCH')<button class="rounded-xl bg-blue-50 px-3 py-2 text-xs font-bold text-primary hover:bg-blue-100">Jadikan Utama</button></form>
                                @endunless
                                <form method="POST" action="{{ route('account.addresses.destroy', $address) }}" data-confirm="Alamat {{ $address->label }} akan dihapus permanen dari akun." data-confirm-title="Hapus alamat?" data-confirm-icon="warning" data-confirm-button="Ya, hapus">@csrf @method('DELETE')<button class="rounded-xl bg-red-50 px-3 py-2 text-xs font-bold text-red-600 hover:bg-red-100">Hapus</button></form>
                            </div>
                        </div>

                        <form x-cloak x-show="editing" method="POST" action="{{ route('account.addresses.update', $address) }}" class="space-y-4" data-confirm="Perubahan alamat akan disimpan ke akun Anda." data-confirm-title="Simpan perubahan alamat?" data-confirm-button="Ya, simpan">
                            @csrf @method('PUT')
                            <div class="grid gap-4 sm:grid-cols-2">
                                <label class="text-sm font-semibold">Label<input name="label" value="{{ $address->label }}" required class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3"></label>
                                <label class="text-sm font-semibold">Nama penerima<input name="recipient_name" value="{{ $address->recipient_name }}" required class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3"></label>
                                <label class="text-sm font-semibold sm:col-span-2">Nomor HP<input name="phone" value="{{ $address->phone }}" required class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3"></label>
                                <label class="text-sm font-semibold sm:col-span-2">Alamat lengkap<textarea name="full_address" rows="4" required class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3">{{ $address->full_address }}</textarea></label>
                                <label class="flex items-center gap-2 text-sm font-semibold sm:col-span-2"><input name="is_primary" type="checkbox" value="1" @checked($address->is_primary) class="rounded border-slate-300"> Jadikan alamat utama</label>
                            </div>
                            <div class="flex gap-2"><button class="rounded-xl bg-primary px-4 py-2.5 text-sm font-bold text-white">Simpan</button><button type="button" @click="editing = false" class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-bold">Batal</button></div>
                        </form>
                    </article>
                @empty
                    <div class="rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-12 text-center"><i class="fas fa-map-location-dot text-3xl text-slate-300"></i><h2 class="mt-4 font-extrabold">Belum ada alamat</h2><p class="mt-1 text-sm text-slate-500">Tambahkan alamat pertama melalui formulir di samping.</p></div>
                @endforelse
            </section>

            <form method="POST" action="{{ route('account.addresses.store') }}" class="h-fit rounded-2xl border border-slate-200 bg-white p-5 shadow-sm lg:sticky lg:top-6" data-confirm="Alamat baru akan disimpan untuk checkout berikutnya." data-confirm-title="Simpan alamat baru?" data-confirm-button="Ya, simpan">
                @csrf
                <div class="flex items-center gap-3"><span class="grid h-10 w-10 place-items-center rounded-xl bg-blue-50 text-primary"><i class="fas fa-location-dot"></i></span><h2 class="font-extrabold text-slate-900">Tambah alamat</h2></div>
                <div class="mt-5 space-y-4">
                    <label class="block text-sm font-semibold">Label alamat<input name="label" value="{{ old('label') }}" placeholder="Rumah, Kantor" required class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3"></label>
                    <label class="block text-sm font-semibold">Nama penerima<input name="recipient_name" value="{{ old('recipient_name', auth()->user()->name) }}" required class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3"></label>
                    <label class="block text-sm font-semibold">Nomor HP<input name="phone" value="{{ old('phone', auth()->user()->phone) }}" required class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3"></label>
                    <label class="block text-sm font-semibold">Alamat lengkap<textarea name="full_address" rows="4" required class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3">{{ old('full_address') }}</textarea></label>
                    <label class="flex items-center gap-2 text-sm font-semibold"><input name="is_primary" type="checkbox" value="1" class="rounded border-slate-300"> Jadikan alamat utama</label>
                </div>
                <button class="mt-5 w-full rounded-xl bg-primary px-4 py-3 font-bold text-white hover:bg-secondary">Simpan Alamat</button>
            </form>
        </div>
    </div>
</x-layouts.app>
