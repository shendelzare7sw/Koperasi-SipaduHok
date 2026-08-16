<div x-data="{ notificationOpen: false }" class="relative">
    <button type="button" @click="notificationOpen = ! notificationOpen" class="relative grid h-10 w-10 place-items-center rounded-full border border-white/25 bg-white/10 text-white transition hover:bg-white/20" aria-label="Buka notifikasi" :aria-expanded="notificationOpen">
        <i class="fas fa-bell" aria-hidden="true"></i>
        @if($unreadNotificationCount > 0)
            <span class="absolute -right-1 -top-1 grid h-5 min-w-5 place-items-center rounded-full bg-accent-yellow px-1 text-[10px] font-black text-slate-950 ring-2 ring-primary">
                {{ $unreadNotificationCount > 99 ? '99+' : $unreadNotificationCount }}
            </span>
        @endif
    </button>

    <div x-cloak x-show="notificationOpen" x-transition.origin.top.right @click.outside="notificationOpen = false" class="absolute right-0 z-50 mt-3 w-[min(24rem,calc(100vw-2rem))] overflow-hidden rounded-2xl border border-slate-200 bg-white text-slate-800 shadow-2xl">
        <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
            <div>
                <p class="font-extrabold text-slate-900">Notifikasi</p>
                <p class="text-xs text-slate-500">{{ $unreadNotificationCount }} belum dibaca</p>
            </div>
            <a href="{{ route('notifications.index') }}" class="text-xs font-bold text-primary hover:text-secondary">Lihat semua</a>
        </div>

        <div class="max-h-96 overflow-y-auto">
            @forelse($latestNotifications as $notification)
                <form method="POST" action="{{ route('notifications.open', $notification) }}" data-confirm="Notifikasi akan ditandai sudah dibaca dan detail pesanannya dibuka." data-confirm-title="Buka detail pesanan?" data-confirm-button="Ya, buka">
                    @csrf
                    <button class="flex w-full gap-3 border-b border-slate-100 px-4 py-3 text-left transition hover:bg-blue-50 {{ $notification->read_at ? 'bg-white' : 'bg-blue-50/60' }}">
                        <span class="mt-0.5 grid h-9 w-9 shrink-0 place-items-center rounded-xl {{ $notification->read_at ? 'bg-slate-100 text-slate-500' : 'bg-primary text-white' }}">
                            <i class="fas {{ $notification->data['icon'] ?? 'fa-bell' }} text-sm" aria-hidden="true"></i>
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="flex items-center gap-2">
                                <span class="truncate text-sm font-extrabold text-slate-900">{{ $notification->data['title'] ?? 'Aktivitas pesanan' }}</span>
                                @unless($notification->read_at)<span class="h-2 w-2 shrink-0 rounded-full bg-primary"></span>@endunless
                            </span>
                            <span class="mt-0.5 block line-clamp-2 text-xs leading-5 text-slate-500">{{ $notification->data['message'] ?? '' }}</span>
                            <span class="mt-1 block text-[11px] text-slate-400">{{ $notification->created_at->diffForHumans() }}</span>
                        </span>
                    </button>
                </form>
            @empty
                <div class="px-6 py-10 text-center">
                    <i class="fas fa-bell-slash text-2xl text-slate-300" aria-hidden="true"></i>
                    <p class="mt-3 text-sm font-bold text-slate-600">Belum ada notifikasi</p>
                </div>
            @endforelse
        </div>

        @if($unreadNotificationCount > 0)
            <form method="POST" action="{{ route('notifications.read-all') }}" class="border-t border-slate-100 p-3" data-confirm="Seluruh notifikasi belum dibaca akan ditandai sudah dibaca." data-confirm-title="Tandai semua sudah dibaca?" data-confirm-button="Ya, tandai semua">
                @csrf
                <button class="w-full rounded-xl px-3 py-2 text-sm font-bold text-primary transition hover:bg-blue-50"><i class="fas fa-check-double mr-2" aria-hidden="true"></i>Baca Semua</button>
            </form>
        @endif
    </div>
</div>
