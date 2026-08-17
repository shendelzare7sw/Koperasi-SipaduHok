<x-layouts.app title="Kelola Pembeli">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div><p class="text-sm font-extrabold uppercase tracking-widest text-primary">Pengguna Koperasi</p><h1 class="mt-1 text-3xl font-black text-slate-900">Kelola Pembeli</h1><p class="mt-1 text-slate-500">Tinjau akun, status akses, riwayat transaksi, dan verifikasi KTP pembeli.</p><p class="mt-2 text-xs font-bold text-slate-500">{{ $activeBuyerCount }} aktif <span class="mx-1">&bull;</span> {{ $inactiveBuyerCount }} nonaktif</p></div>
        <a href="{{ route('admin.buyers.index', ['verification' => 'pending']) }}" class="inline-flex items-center justify-center gap-2 rounded-xl border border-amber-300 bg-amber-50 px-4 py-3 font-bold text-amber-700"><i class="fas fa-id-card" aria-hidden="true"></i>{{ $pendingVerificationCount }} menunggu verifikasi</a>
    </div>

    <form method="GET" class="mt-6 grid gap-3 rounded-2xl border border-slate-200 bg-white p-4 lg:grid-cols-[minmax(0,1fr)_200px_180px_auto]">
        <input name="search" value="{{ request('search') }}" placeholder="Nama, email, atau nomor HP" class="rounded-xl border border-slate-300 px-4 py-3 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20">
        <select name="verification" class="rounded-xl border border-slate-300 bg-white px-4 py-3">
            <option value="">Semua status KTP</option>
            <option value="not_submitted" @selected(request('verification') === 'not_submitted')>Belum mengirim</option>
            <option value="pending" @selected(request('verification') === 'pending')>Menunggu admin</option>
            <option value="verified" @selected(request('verification') === 'verified')>Terverifikasi</option>
            <option value="rejected" @selected(request('verification') === 'rejected')>Ditolak</option>
        </select>
        <select name="account_status" class="rounded-xl border border-slate-300 bg-white px-4 py-3">
            <option value="">Semua status akun</option>
            <option value="active" @selected(request('account_status') === 'active')>Akun aktif</option>
            <option value="inactive" @selected(request('account_status') === 'inactive')>Akun nonaktif</option>
        </select>
        <button class="rounded-xl bg-primary px-5 py-3 font-bold text-white transition hover:bg-secondary">Terapkan</button>
    </form>

    <div class="mt-5 overflow-x-auto rounded-2xl border border-slate-200 bg-white">
        <table class="min-w-full text-sm">
            <thead class="bg-blue-50/70 text-left text-xs uppercase tracking-wide text-slate-500"><tr><th class="p-4">Pembeli</th><th class="p-4">Status akun</th><th class="p-4">Status KTP</th><th class="p-4">Kontak</th><th class="p-4 text-center">Pesanan</th><th class="p-4 text-right">Belanja Selesai</th><th class="p-4 text-right">Aksi</th></tr></thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($buyers as $buyer)
                    @php $status = $buyer->identityVerification?->status ?? 'not_submitted'; @endphp
                    <tr class="hover:bg-blue-50/40">
                        <td class="p-4"><p class="font-bold text-slate-900">{{ $buyer->name }}</p><p class="text-xs text-slate-500">ID KSP-{{ str_pad((string) $buyer->id, 6, '0', STR_PAD_LEFT) }} · {{ $buyer->created_at->format('d/m/Y') }}</p></td>
                        <td class="p-4"><span class="inline-flex rounded-full px-2.5 py-1 text-[10px] font-extrabold uppercase tracking-wide {{ $buyer->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700' }}">{{ $buyer->is_active ? 'Aktif' : 'Nonaktif' }}</span></td>
                        <td class="p-4"><span class="inline-flex rounded-full px-2.5 py-1 text-[10px] font-extrabold uppercase tracking-wide {{ $status === 'verified' ? 'bg-emerald-100 text-emerald-700' : ($status === 'rejected' ? 'bg-red-100 text-red-700' : ($status === 'pending' ? 'bg-amber-100 text-amber-700' : 'bg-slate-100 text-slate-600')) }}">{{ $buyer->identityVerification?->statusLabel() ?? 'Belum Mengirim' }}</span></td>
                        <td class="p-4"><p>{{ $buyer->email }}</p><p class="text-xs text-slate-500">{{ $buyer->phone }}</p></td>
                        <td class="p-4 text-center font-bold">{{ $buyer->orders_count }}</td>
                        <td class="p-4 text-right font-black">Rp {{ number_format($buyer->completed_spend ?? 0, 0, ',', '.') }}</td>
                        <td class="p-4 text-right"><a href="{{ route('admin.buyers.show', $buyer) }}" class="inline-flex items-center gap-2 rounded-lg bg-blue-50 px-3 py-2 text-xs font-bold text-primary hover:bg-blue-100">Detail <i class="fas fa-arrow-right" aria-hidden="true"></i></a></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="p-10 text-center text-slate-500">Pembeli tidak ditemukan.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-6">{{ $buyers->links() }}</div>
</x-layouts.app>
