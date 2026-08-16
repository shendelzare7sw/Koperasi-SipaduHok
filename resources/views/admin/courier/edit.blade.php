<x-layouts.app title="Pengaturan Kurir Koperasi">
    <div class="mx-auto max-w-2xl">
        <p class="font-bold uppercase tracking-wide text-orange-600">Satu Kurir Utama</p>
        <h1 class="mt-1 text-3xl font-black">Pengaturan Kurir Koperasi</h1>
        <p class="mt-2 text-slate-500">Tidak terhubung ke API ongkir. Tarif flat ini dipakai otomatis pada seluruh checkout baru.</p>
        <form method="POST" action="{{ route('admin.courier.update') }}" class="mt-6 space-y-4 rounded-2xl border border-slate-200 bg-white p-6">@csrf @method('PUT')
            <label class="block text-sm font-semibold">Nama kurir<input name="name" value="{{ old('name', $courier->name) }}" required class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3"></label>
            <label class="block text-sm font-semibold">Tarif flat (Rp)<input name="fee" type="number" min="0" value="{{ old('fee', $courier->fee) }}" required class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3"></label>
            <label class="block text-sm font-semibold">Estimasi<input name="estimate" value="{{ old('estimate', $courier->estimate) }}" placeholder="Contoh: 1 hari sekolah" class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3"></label>
            <label class="flex items-center gap-2 text-sm font-semibold"><input name="is_active" type="checkbox" value="1" @checked(old('is_active', $courier->is_active)) class="rounded border-slate-300"> Kurir aktif dan checkout dapat digunakan</label>
            <button class="w-full rounded-xl bg-orange-500 px-5 py-3 font-bold text-slate-950">Simpan Pengaturan Kurir</button>
        </form>
    </div>
</x-layouts.app>
