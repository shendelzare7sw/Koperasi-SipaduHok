<x-layouts.app title="Notifikasi - Koperasi Sipaduhok">
    <div class="mx-auto max-w-4xl">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-extrabold uppercase tracking-widest text-secondary">Pusat informasi</p>
                <h1 class="mt-1 text-3xl font-black text-slate-900">Notifikasi</h1>
                <p class="mt-2 text-sm text-slate-500">Pantau aktivitas pembayaran dan perjalanan pesanan.</p>
            </div>
            @if(auth()->user()->unreadNotifications()->exists())
                <form method="POST" action="{{ route('notifications.read-all') }}" data-confirm="Seluruh notifikasi belum dibaca akan ditandai sudah dibaca." data-confirm-title="Tandai semua sudah dibaca?" data-confirm-button="Ya, tandai semua">
                    @csrf
                    <button class="inline-flex items-center justify-center gap-2 rounded-xl border border-primary px-4 py-3 text-sm font-bold text-primary transition hover:bg-blue-50">
                        <i class="fas fa-check-double" aria-hidden="true"></i>
                        Baca Semua
                    </button>
                </form>
            @endif
        </div>

        <div class="mt-6 flex gap-2 border-b border-slate-200">
            <a href="{{ route('notifications.index') }}" class="border-b-2 px-4 py-3 text-sm font-bold {{ request('filter') !== 'unread' ? 'border-primary text-primary' : 'border-transparent text-slate-500 hover:text-primary' }}">Semua</a>
            <a href="{{ route('notifications.index', ['filter' => 'unread']) }}" class="border-b-2 px-4 py-3 text-sm font-bold {{ request('filter') === 'unread' ? 'border-primary text-primary' : 'border-transparent text-slate-500 hover:text-primary' }}">Belum Dibaca</a>
        </div>

        <div class="mt-5 space-y-3">
            @forelse($notifications as $notification)
                <article class="rounded-2xl border p-4 transition sm:p-5 {{ $notification->read_at ? 'border-slate-200 bg-white' : 'border-primary/25 bg-blue-50/60 shadow-sm' }}">
                    <div class="flex gap-4">
                        <span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl {{ $notification->read_at ? 'bg-slate-100 text-slate-500' : 'bg-primary text-white' }}">
                            <i class="fas {{ $notification->data['icon'] ?? 'fa-bell' }}" aria-hidden="true"></i>
                        </span>
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-start justify-between gap-2">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <h2 class="font-extrabold text-slate-900">{{ $notification->data['title'] ?? 'Aktivitas pesanan' }}</h2>
                                        @unless($notification->read_at)<span class="h-2 w-2 rounded-full bg-primary" title="Belum dibaca"></span>@endunless
                                    </div>
                                    <p class="mt-1 text-sm leading-6 text-slate-600">{{ $notification->data['message'] ?? '' }}</p>
                                </div>
                                <time class="whitespace-nowrap text-xs text-slate-400">{{ $notification->created_at->diffForHumans() }}</time>
                            </div>
                            <form method="POST" action="{{ route('notifications.open', $notification) }}" class="mt-3" data-confirm="Notifikasi akan ditandai sudah dibaca dan detail pesanannya dibuka." data-confirm-title="Buka detail pesanan?" data-confirm-button="Ya, buka">
                                @csrf
                                <button class="inline-flex items-center gap-2 text-sm font-bold text-primary hover:text-secondary">Lihat Pesanan <i class="fas fa-arrow-right text-xs" aria-hidden="true"></i></button>
                            </form>
                        </div>
                    </div>
                </article>
            @empty
                <div class="rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-14 text-center">
                    <span class="mx-auto grid h-14 w-14 place-items-center rounded-2xl bg-blue-50 text-xl text-primary"><i class="fas fa-bell-slash" aria-hidden="true"></i></span>
                    <h2 class="mt-4 font-extrabold text-slate-900">Belum ada notifikasi</h2>
                    <p class="mt-1 text-sm text-slate-500">Informasi pesanan terbaru akan tampil di sini.</p>
                </div>
            @endforelse
        </div>

        <div class="mt-6">{{ $notifications->links() }}</div>
    </div>
</x-layouts.app>
