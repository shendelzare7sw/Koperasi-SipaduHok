<nav class="grid gap-2 {{ auth()->user()->isAdmin() ? 'sm:grid-cols-2' : 'sm:grid-cols-3' }}" aria-label="Pengaturan akun">
    <a href="{{ route('account.profile.edit') }}" class="flex items-center gap-3 rounded-2xl border px-4 py-3 font-bold transition {{ request()->routeIs('account.profile.*') ? 'border-primary bg-blue-50 text-primary' : 'border-slate-200 bg-white text-slate-600 hover:border-primary/40 hover:text-primary' }}">
        <i class="fas fa-user-pen w-5 text-center" aria-hidden="true"></i>
        Profil Saya
    </a>
    <a href="{{ route('account.security.edit') }}" class="flex items-center gap-3 rounded-2xl border px-4 py-3 font-bold transition {{ request()->routeIs('account.security.*') ? 'border-primary bg-blue-50 text-primary' : 'border-slate-200 bg-white text-slate-600 hover:border-primary/40 hover:text-primary' }}">
        <i class="fas fa-shield-halved w-5 text-center" aria-hidden="true"></i>
        Keamanan Akun
    </a>
    @unless(auth()->user()->isAdmin())
        <a href="{{ route('account.addresses.index') }}" class="flex items-center gap-3 rounded-2xl border px-4 py-3 font-bold transition {{ request()->routeIs('account.addresses.*') ? 'border-primary bg-blue-50 text-primary' : 'border-slate-200 bg-white text-slate-600 hover:border-primary/40 hover:text-primary' }}">
            <i class="fas fa-location-dot w-5 text-center" aria-hidden="true"></i>
            Alamat Tersimpan
        </a>
    @endunless
</nav>
