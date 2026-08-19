<x-layouts.app title="Verifikasi KTP - Toko Sipaduhok">
    @php
        $status = $verification?->status ?? 'not_submitted';
        $verified = $status === App\Models\IdentityVerification::STATUS_VERIFIED;
    @endphp

    <div class="mx-auto max-w-5xl">
        <div class="mb-6">
            <p class="text-sm font-extrabold uppercase tracking-widest text-secondary">Keamanan transaksi</p>
            <h1 class="mt-1 text-3xl font-black text-slate-900">Verifikasi Identitas Pembeli</h1>
            <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-500">Setiap pembeli wajib mengirim KTP dan menunggu persetujuan admin sebelum checkout. Dokumen disimpan pada storage privat dan tidak dapat dibuka melalui URL publik.</p>
        </div>

        @include('account._navigation')

        <div class="mt-6 grid gap-6 lg:grid-cols-[320px_minmax(0,1fr)]">
            <aside class="h-fit space-y-5">
                <section class="rounded-2xl border p-5 {{ $verified ? 'border-emerald-200 bg-emerald-50' : ($status === 'rejected' ? 'border-red-200 bg-red-50' : 'border-amber-200 bg-amber-50') }}">
                    <span class="grid h-12 w-12 place-items-center rounded-xl text-lg text-white {{ $verified ? 'bg-emerald-600' : ($status === 'rejected' ? 'bg-red-600' : 'bg-amber-500') }}"><i class="fas {{ $verified ? 'fa-circle-check' : ($status === 'pending' ? 'fa-clock' : ($status === 'rejected' ? 'fa-triangle-exclamation' : 'fa-id-card')) }}" aria-hidden="true"></i></span>
                    <h2 class="mt-4 font-black text-slate-900">{{ $verification?->statusLabel() ?? 'Belum Mengirim KTP' }}</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-600">
                        @if($verified) Identitas telah disetujui. Akun dapat melakukan checkout.
                        @elseif($status === 'pending') Dokumen sedang berada dalam antrean pemeriksaan admin.
                        @elseif($status === 'rejected') Periksa alasan penolakan dan kirim ulang dokumen yang benar.
                        @else Unggah dokumen untuk memulai pemeriksaan identitas.
                        @endif
                    </p>
                    @if($verification?->submitted_at)<p class="mt-3 text-xs text-slate-500">Dikirim {{ $verification->submitted_at->translatedFormat('d F Y H:i') }}</p>@endif
                    @if($verification?->review_note)<div class="mt-4 rounded-xl border border-red-200 bg-white/70 p-3 text-xs leading-5 text-red-700"><strong>Catatan admin:</strong><br>{{ $verification->review_note }}</div>@endif
                </section>

                @if($verification)
                    <section class="rounded-2xl border border-slate-200 bg-white p-5">
                        <h2 class="font-extrabold text-slate-900">Dokumen tersimpan</h2>
                        <dl class="mt-3 space-y-2 text-sm"><div><dt class="text-xs text-slate-400">Nama pada KTP</dt><dd class="font-bold">{{ $verification->legal_name }}</dd></div><div><dt class="text-xs text-slate-400">NIK</dt><dd class="font-mono font-bold">{{ $verification->maskedNik() }}</dd></div></dl>
                        <div class="mt-4"><x-image-lightbox :src="route('identity.document', $verification)" alt="Dokumen KTP tersimpan" image-class="max-h-56 w-full bg-slate-100 object-contain" /></div>
                    </section>
                @endif
            </aside>

            @if($verified)
                <section class="h-fit rounded-2xl border border-emerald-200 bg-white p-6 shadow-sm sm:p-8">
                    <div class="flex items-start gap-4"><span class="grid h-12 w-12 shrink-0 place-items-center rounded-xl bg-emerald-100 text-emerald-600"><i class="fas fa-shield-halved text-xl" aria-hidden="true"></i></span><div><h2 class="text-xl font-black text-slate-900">Verifikasi selesai</h2><p class="mt-2 text-sm leading-6 text-slate-600">Untuk mencegah perubahan identitas tanpa pemeriksaan, data KTP yang sudah disetujui tidak dapat diganti dari akun pembeli. Hubungi admin toko bila terdapat kesalahan data.</p><a href="{{ route('catalog.index') }}" class="mt-5 inline-flex items-center gap-2 rounded-xl bg-primary px-5 py-3 font-bold text-white hover:bg-secondary"><i class="fas fa-store" aria-hidden="true"></i>Mulai Belanja</a></div></div>
                </section>
            @else
                <form method="POST" action="{{ route('account.identity.update') }}" enctype="multipart/form-data" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-7" x-data="{ previewUrl: null }" data-confirm="Pastikan nama, NIK, dan foto KTP sudah benar. Dokumen akan dikirim ke admin untuk diperiksa." data-confirm-title="Kirim verifikasi KTP?" data-confirm-button="Ya, kirim">
                    @csrf
                    <div class="flex items-center gap-3 border-b border-slate-100 pb-5"><span class="grid h-11 w-11 place-items-center rounded-xl bg-blue-50 text-primary"><i class="fas fa-address-card" aria-hidden="true"></i></span><div><h2 class="font-extrabold text-slate-900">{{ $verification ? 'Kirim Ulang Dokumen' : 'Data Identitas' }}</h2><p class="text-xs text-slate-500">Gunakan data yang sama persis dengan KTP.</p></div></div>

                    <div class="mt-6 grid gap-5 sm:grid-cols-2">
                        <label class="text-sm font-semibold sm:col-span-2">Nama lengkap sesuai KTP
                            <input name="legal_name" value="{{ old('legal_name', $verification?->legal_name ?? auth()->user()->name) }}" required maxlength="255" autocomplete="name" class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20">
                        </label>
                        <label class="text-sm font-semibold sm:col-span-2">NIK
                            <input name="nik" value="{{ old('nik') }}" required inputmode="numeric" pattern="[0-9]{16}" minlength="16" maxlength="16" autocomplete="off" placeholder="16 digit NIK" class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3 font-mono tracking-wider focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20">
                            <span class="mt-1 block text-xs font-normal text-slate-400">NIK dienkripsi; sistem hanya memakai hash terpisah untuk mencegah satu NIK digunakan pada beberapa akun.</span>
                        </label>
                        <label class="text-sm font-semibold sm:col-span-2">Foto KTP
                            <input name="identity_document" type="file" required accept="image/jpeg,image/png,image/webp" @change="previewUrl && URL.revokeObjectURL(previewUrl); previewUrl = $event.target.files[0] ? URL.createObjectURL($event.target.files[0]) : null" class="mt-1 block w-full rounded-xl border border-slate-300 bg-white px-3 py-3 text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-blue-50 file:px-3 file:py-2 file:font-bold file:text-primary">
                            <span class="mt-1 block text-xs font-normal text-slate-400">JPG, JPEG, PNG, atau WebP. Maksimal 5 MB. Pastikan seluruh sisi KTP terlihat dan teks terbaca.</span>
                        </label>
                        <div x-cloak x-show="previewUrl" class="sm:col-span-2"><p class="mb-2 text-xs font-bold uppercase tracking-wider text-slate-400">Pratinjau sebelum dikirim</p><button type="button" @click="$dispatch('open-identity-preview')" class="block w-full cursor-zoom-in overflow-hidden rounded-xl border border-slate-200 bg-slate-100"><img :src="previewUrl" alt="Pratinjau KTP baru" class="max-h-72 w-full object-contain"></button><div x-data="{ open: false }" @open-identity-preview.window="open = true" @keydown.escape.window="open = false"><div x-cloak x-show="open" class="fixed inset-0 z-[95] grid place-items-center bg-slate-950/90 p-4"><button type="button" @click="open = false" class="absolute right-5 top-5 grid h-11 w-11 place-items-center rounded-full bg-white"><i class="fas fa-xmark"></i></button><img :src="previewUrl" alt="Pratinjau KTP diperbesar" class="max-h-[90vh] max-w-full rounded-xl object-contain"></div></div></div>
                        <label class="flex items-start gap-3 rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm sm:col-span-2"><input type="checkbox" name="consent" value="1" required @checked(old('consent')) class="mt-1 rounded border-slate-300 text-primary"><span><strong class="block text-slate-900">Saya menyetujui pemeriksaan identitas</strong><span class="mt-1 block text-xs font-normal leading-5 text-slate-500">Saya memahami KTP digunakan khusus untuk verifikasi akun dan pencegahan penyalahgunaan transaksi oleh admin toko yang berwenang.</span></span></label>
                    </div>

                    <div class="mt-7 flex justify-end"><button class="rounded-xl bg-primary px-5 py-3 font-bold text-white hover:bg-secondary"><i class="fas fa-paper-plane mr-2" aria-hidden="true"></i>Kirim ke Admin</button></div>
                </form>
            @endif
        </div>
    </div>
</x-layouts.app>
