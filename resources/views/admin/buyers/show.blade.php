<x-layouts.app title="Pembeli {{ $buyer->name }}">
    @php
        $verification = $buyer->identityVerification;
        $status = $verification?->status ?? 'not_submitted';
    @endphp

    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div><p class="text-sm font-extrabold uppercase tracking-widest text-primary">Detail Pembeli</p><h1 class="mt-1 text-3xl font-black text-slate-900">{{ $buyer->name }}</h1><p class="mt-1 text-slate-500">KSP-{{ str_pad((string) $buyer->id, 6, '0', STR_PAD_LEFT) }} · Terdaftar {{ $buyer->created_at->translatedFormat('d F Y') }}</p></div>
        <div class="flex flex-wrap gap-2"><span class="inline-flex w-fit rounded-full px-3 py-2 text-xs font-extrabold uppercase tracking-wide {{ $buyer->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700' }}">Akun {{ $buyer->is_active ? 'aktif' : 'nonaktif' }}</span><span class="inline-flex w-fit rounded-full px-3 py-2 text-xs font-extrabold uppercase tracking-wide {{ $status === 'verified' ? 'bg-emerald-100 text-emerald-700' : ($status === 'rejected' ? 'bg-red-100 text-red-700' : ($status === 'pending' ? 'bg-amber-100 text-amber-700' : 'bg-slate-100 text-slate-600')) }}">{{ $verification?->statusLabel() ?? 'Belum Mengirim KTP' }}</span></div>
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-[minmax(0,1.35fr)_minmax(320px,0.65fr)]">
        <div class="space-y-6">
            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                <div class="flex items-center gap-3"><span class="grid h-11 w-11 place-items-center rounded-xl bg-blue-50 text-primary"><i class="fas fa-id-card" aria-hidden="true"></i></span><div><h2 class="font-black text-slate-900">Pemeriksaan KTP</h2><p class="text-xs text-slate-500">Dokumen privat hanya dapat diakses pembeli pemilik dan admin.</p></div></div>

                @if($verification)
                    <dl class="mt-5 grid gap-4 rounded-xl bg-slate-50 p-4 text-sm sm:grid-cols-2"><div><dt class="text-xs text-slate-400">Nama sesuai KTP</dt><dd class="mt-1 font-bold text-slate-900">{{ $verification->legal_name }}</dd></div><div><dt class="text-xs text-slate-400">NIK</dt><dd class="mt-1 font-mono font-bold text-slate-900">{{ $verification->nik }}</dd></div><div><dt class="text-xs text-slate-400">Dikirim</dt><dd class="mt-1 font-bold">{{ $verification->submitted_at->format('d/m/Y H:i') }}</dd></div><div><dt class="text-xs text-slate-400">Ditinjau</dt><dd class="mt-1 font-bold">{{ $verification->reviewed_at?->format('d/m/Y H:i') ?? '-' }}</dd></div></dl>
                    <div class="mt-5"><x-image-lightbox :src="route('identity.document', $verification)" alt="KTP {{ $buyer->name }}" image-class="max-h-[32rem] w-full bg-slate-100 object-contain" /></div>
                    @if($verification->review_note)<div class="mt-4 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700"><strong>Catatan penolakan:</strong><br>{{ $verification->review_note }}</div>@endif

                    @if($status === 'pending')
                        <div class="mt-5 grid gap-4 border-t border-slate-100 pt-5 lg:grid-cols-2">
                            <form method="POST" action="{{ route('admin.buyers.identity.approve', $buyer) }}" data-confirm="Pastikan foto KTP, nama, dan NIK milik pembeli sudah cocok." data-confirm-title="Setujui identitas pembeli?" data-confirm-button="Ya, setujui">@csrf @method('PATCH')<button class="w-full rounded-xl bg-emerald-600 px-5 py-3 font-bold text-white hover:bg-emerald-700"><i class="fas fa-circle-check mr-2" aria-hidden="true"></i>Setujui KTP</button></form>
                            <form method="POST" action="{{ route('admin.buyers.identity.reject', $buyer) }}" data-confirm="Pembeli akan menerima alasan penolakan dan diminta mengirim dokumen baru." data-confirm-title="Tolak verifikasi KTP?" data-confirm-icon="warning" data-confirm-button="Ya, tolak">@csrf @method('PATCH')<textarea name="review_note" rows="3" required minlength="10" maxlength="1000" placeholder="Jelaskan alasan: foto buram, data tidak cocok, bagian terpotong, dan sebagainya." class="w-full rounded-xl border border-red-200 px-4 py-3 text-sm"></textarea><button class="mt-2 w-full rounded-xl bg-red-600 px-5 py-3 font-bold text-white hover:bg-red-700"><i class="fas fa-xmark mr-2" aria-hidden="true"></i>Tolak & Minta Perbaikan</button></form>
                        </div>
                    @endif
                @else
                    <div class="mt-5 rounded-xl border border-dashed border-slate-300 p-8 text-center"><i class="fas fa-file-circle-xmark text-3xl text-slate-300" aria-hidden="true"></i><p class="mt-3 font-bold text-slate-700">Pembeli belum mengirim dokumen KTP.</p></div>
                @endif
            </section>

            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white"><div class="border-b border-slate-100 p-5"><h2 class="font-black">Pesanan Terbaru</h2></div><div class="divide-y divide-slate-100">@forelse($buyer->orders as $order)<a href="{{ route('admin.orders.show', $order) }}" class="flex items-center justify-between gap-4 p-4 hover:bg-blue-50"><span><span class="block font-mono text-sm font-bold text-primary">{{ $order->invoice_number }}</span><span class="text-xs text-slate-500">{{ $order->statusLabel() }}</span></span><span class="font-black">Rp {{ number_format($order->total, 0, ',', '.') }}</span></a>@empty<p class="p-8 text-center text-sm text-slate-500">Belum ada pesanan.</p>@endforelse</div></section>
        </div>

        <aside class="space-y-6">
            <section class="rounded-2xl border border-slate-200 bg-white p-5"><h2 class="font-black text-slate-900">Informasi Akun</h2><dl class="mt-4 space-y-4 text-sm"><div><dt class="text-xs text-slate-400">Nama akun</dt><dd class="mt-1 font-bold">{{ $buyer->name }}</dd></div><div><dt class="text-xs text-slate-400">Email terverifikasi</dt><dd class="mt-1 break-all font-bold">{{ $buyer->email }}</dd></div><div><dt class="text-xs text-slate-400">Nomor HP</dt><dd class="mt-1 font-bold">{{ $buyer->phone }}</dd></div><div><dt class="text-xs text-slate-400">Jumlah alamat</dt><dd class="mt-1 font-bold">{{ $buyer->addresses->count() }}</dd></div></dl><form method="POST" action="{{ route('admin.buyers.toggle-active', $buyer) }}" class="mt-5 border-t border-slate-100 pt-5" data-confirm="{{ $buyer->is_active ? 'Pembeli akan langsung keluar dari seluruh sesi dan tidak dapat login.' : 'Pembeli akan dapat login dan menggunakan akunnya kembali.' }}" data-confirm-title="{{ $buyer->is_active ? 'Nonaktifkan akun pembeli?' : 'Aktifkan kembali akun?' }}" data-confirm-icon="warning" data-confirm-button="Ya, lanjutkan">@csrf @method('PATCH')<button class="w-full rounded-xl px-4 py-3 font-bold text-white {{ $buyer->is_active ? 'bg-red-600 hover:bg-red-700' : 'bg-emerald-600 hover:bg-emerald-700' }}"><i class="fas {{ $buyer->is_active ? 'fa-user-slash' : 'fa-user-check' }} mr-2" aria-hidden="true"></i>{{ $buyer->is_active ? 'Nonaktifkan Akun' : 'Aktifkan Kembali' }}</button></form></section>
            <section class="rounded-2xl border border-amber-200 bg-amber-50 p-5 text-xs leading-5 text-amber-800"><strong>Data pribadi sensitif</strong><p class="mt-2">Gunakan NIK dan foto KTP hanya untuk pemeriksaan identitas. Jangan mengunduh, menyebarkan, atau mengirimkannya melalui kanal yang tidak aman.</p></section>
        </aside>
    </div>
</x-layouts.app>
