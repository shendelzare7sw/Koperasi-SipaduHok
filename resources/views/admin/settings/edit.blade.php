<x-layouts.app title="Identitas Koperasi">
    <div class="max-w-3xl">
        <p class="text-sm font-extrabold uppercase tracking-widest text-secondary">Pengaturan toko</p>
        <h1 class="mt-1 text-3xl font-black text-slate-900">Identitas & Kontak Koperasi</h1>
        <p class="mt-2 text-sm leading-6 text-slate-500">Informasi ini tampil pada footer serta halaman informasi/legal untuk membantu pembeli dan verifikasi payment gateway.</p>

        <form method="POST" action="{{ route('admin.settings.store.update') }}" class="mt-6 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-7" data-confirm="Identitas dan kontak publik Koperasi Sipaduhok akan diperbarui." data-confirm-title="Simpan identitas koperasi?" data-confirm-button="Ya, simpan">
            @csrf @method('PUT')
            <div class="grid gap-5 sm:grid-cols-2">
                <label class="text-sm font-semibold sm:col-span-2">Nama legal/usaha<input name="legal_name" value="{{ old('legal_name', $settings['legal_name']) }}" required class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3"></label>
                <label class="text-sm font-semibold">Email dukungan<input name="support_email" type="email" value="{{ old('support_email', $settings['support_email']) }}" required class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3"></label>
                <label class="text-sm font-semibold">Nomor telepon<input name="phone" value="{{ old('phone', $settings['phone']) }}" class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3"></label>
                <label class="text-sm font-semibold">WhatsApp<input name="whatsapp" value="{{ old('whatsapp', $settings['whatsapp']) }}" class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3"></label>
                <label class="text-sm font-semibold">Jam operasional<input name="operating_hours" value="{{ old('operating_hours', $settings['operating_hours']) }}" required class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3"></label>
                <label class="text-sm font-semibold sm:col-span-2">Alamat koperasi<textarea name="address" rows="4" required class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3">{{ old('address', $settings['address']) }}</textarea></label>
                <label class="text-sm font-semibold sm:col-span-2">Deskripsi usaha<textarea name="description" rows="4" required class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3">{{ old('description', $settings['description']) }}</textarea></label>
            </div>
            <div class="mt-7 flex justify-end"><button class="rounded-xl bg-primary px-5 py-3 font-bold text-white hover:bg-secondary"><i class="fas fa-floppy-disk mr-2"></i>Simpan Pengaturan</button></div>
        </form>
    </div>
</x-layouts.app>
