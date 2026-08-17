<div x-data="{ accountOpen: false }" class="relative">
    <button type="button" @click="accountOpen = ! accountOpen" class="flex items-center gap-2 rounded-full border border-white/25 bg-white/10 p-1 pr-3 text-left transition hover:bg-white/20" aria-label="Buka menu akun" :aria-expanded="accountOpen">
        <span class="grid h-8 w-8 place-items-center rounded-full bg-white font-black text-primary">{{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</span>
        <span class="hidden max-w-28 truncate text-xs font-bold xl:block">{{ auth()->user()->name }}</span>
        <i class="fas fa-chevron-down text-[10px] transition" :class="accountOpen && 'rotate-180'" aria-hidden="true"></i>
    </button>

    <div x-cloak x-show="accountOpen" x-transition.origin.top.right @click.outside="accountOpen = false" class="fixed left-4 right-4 top-20 z-50 overflow-hidden rounded-2xl border border-slate-200 bg-white text-slate-800 shadow-2xl sm:absolute sm:left-auto sm:right-0 sm:top-auto sm:mt-3 sm:w-64">
        <div class="bg-gradient-to-r from-primary to-secondary px-4 py-4 text-white">
            <p class="truncate font-extrabold">{{ auth()->user()->name }}</p>
            <p class="mt-0.5 truncate text-xs text-blue-100">{{ auth()->user()->email }}</p>
            <span class="mt-2 inline-flex rounded-full bg-white/15 px-2.5 py-1 text-[10px] font-extrabold uppercase tracking-wider">{{ auth()->user()->role->label() }}</span>
        </div>
        <nav class="p-2 text-sm">
            <a href="{{ route('account.profile.edit') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 font-semibold text-slate-600 transition hover:bg-blue-50 hover:text-primary"><i class="fas fa-user-pen w-5 text-center" aria-hidden="true"></i>Profil Saya</a>
            <a href="{{ route('account.security.edit') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 font-semibold text-slate-600 transition hover:bg-blue-50 hover:text-primary"><i class="fas fa-gear w-5 text-center" aria-hidden="true"></i>Pengaturan Akun</a>
            @if(auth()->user()->isAdmin())
                <a href="{{ route('admin.settings.store.edit') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 font-semibold text-slate-600 transition hover:bg-blue-50 hover:text-primary"><i class="fas fa-store w-5 text-center" aria-hidden="true"></i>Identitas Koperasi</a>
            @endif
            @unless(auth()->user()->isAdmin())
                <a href="{{ route('account.identity.edit') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 font-semibold text-slate-600 transition hover:bg-blue-50 hover:text-primary"><i class="fas fa-id-card w-5 text-center" aria-hidden="true"></i>Verifikasi KTP</a>
                <a href="{{ route('wishlist.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 font-semibold text-slate-600 transition hover:bg-blue-50 hover:text-primary"><i class="fas fa-heart w-5 text-center" aria-hidden="true"></i><span class="flex-1">Wishlist</span>@if($wishlistCount > 0)<span class="rounded-full bg-primary px-2 py-0.5 text-[10px] font-black text-white">{{ $wishlistCount }}</span>@endif</a>
                <a href="{{ route('account.addresses.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 font-semibold text-slate-600 transition hover:bg-blue-50 hover:text-primary"><i class="fas fa-location-dot w-5 text-center" aria-hidden="true"></i>Alamat Tersimpan</a>
            @endunless
            <a href="{{ route('notifications.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 font-semibold text-slate-600 transition hover:bg-blue-50 hover:text-primary"><i class="fas fa-bell w-5 text-center" aria-hidden="true"></i><span class="flex-1">Notifikasi</span>@if($unreadNotificationCount > 0)<span class="rounded-full bg-primary px-2 py-0.5 text-[10px] font-black text-white">{{ $unreadNotificationCount }}</span>@endif</a>
        </nav>
        <div class="border-t border-slate-100 p-2">
            <form method="POST" action="{{ auth()->user()->isAdmin() ? route('admin.logout') : route('logout') }}" data-confirm="Anda akan keluar dari akun Koperasi Sipaduhok." data-confirm-title="Keluar dari akun?" data-confirm-button="Ya, keluar">
                @csrf
                <button class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-left text-sm font-bold text-red-600 transition hover:bg-red-50"><i class="fas fa-arrow-right-from-bracket w-5 text-center" aria-hidden="true"></i>Keluar</button>
            </form>
        </div>
    </div>
</div>
