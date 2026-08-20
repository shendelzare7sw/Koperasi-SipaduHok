<x-layouts.app title="Pesanan {{ $order->invoice_number }}">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <p class="font-bold uppercase tracking-wide text-primary">Kelola Pesanan</p>
            <h1 class="font-mono text-2xl font-black">{{ $order->invoice_number }}</h1>
            <p class="text-slate-500">{{ $order->student_name }} · {{ $order->class_name }}</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('admin.orders.invoice', $order) }}" class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-bold">Invoice</a>
            <a href="{{ route('admin.orders.label', $order) }}" class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-bold">Label Kirim</a>
            <x-status-badge :status="$order->status" class="px-4 py-2 text-sm" />
        </div>
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-[1fr_360px]">
        <div class="space-y-6">
            <section class="rounded-2xl border border-slate-200 bg-white p-5">
                <h2 class="font-black">Item</h2>
                <div class="mt-4 divide-y divide-slate-100">
                    @foreach($order->items as $item)
                        <div class="flex justify-between gap-3 py-3 text-sm"><span>{{ $item->product_name }} × {{ $item->quantity }}</span><span class="font-bold">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</span></div>
                    @endforeach
                </div>
                <div class="mt-4 flex justify-between border-t border-slate-200 pt-4 text-lg font-black"><span>Total + Kurir</span><span>Rp {{ number_format($order->total, 0, ',', '.') }}</span></div>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-5">
                <h2 class="font-black">Penerima & Alamat</h2>
                <div class="mt-4 grid gap-4 text-sm sm:grid-cols-2">
                    <div><p class="text-slate-500">Pembeli</p><p class="font-bold">{{ $order->buyer_name }}</p><p>{{ $order->phone }}</p></div>
                    <div><p class="text-slate-500">Siswa</p><p class="font-bold">{{ $order->student_name }}</p><p>{{ $order->class_name }}</p></div>
                    <div class="sm:col-span-2"><p class="text-slate-500">Alamat pengantaran</p><p class="mt-1 whitespace-pre-line">{{ $order->delivery_address }}</p></div>
                    @if($order->delivery_maps_url)
                        <div x-data="{ copied: false, async copyUrl() { await navigator.clipboard.writeText({{ Illuminate\Support\Js::from($order->delivery_maps_url) }}); this.copied = true; setTimeout(() => this.copied = false, 1800); } }" class="flex flex-wrap gap-2 sm:col-span-2">
                            <a href="{{ $order->delivery_maps_url }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-2 rounded-xl bg-blue-50 px-4 py-2.5 text-xs font-bold text-primary hover:bg-blue-100"><i class="fas fa-map-location-dot"></i>Buka Navigasi</a>
                            <button type="button" @click="copyUrl()" class="inline-flex items-center gap-2 rounded-xl border border-slate-300 px-4 py-2.5 text-xs font-bold text-slate-700 hover:bg-slate-50"><i class="fas" :class="copied ? 'fa-check text-emerald-600' : 'fa-copy'"></i><span x-text="copied ? 'URL Tersalin' : 'Salin URL untuk Kurir'"></span></button>
                        </div>
                    @else
                        <p class="text-xs font-semibold text-amber-700 sm:col-span-2"><i class="fas fa-triangle-exclamation mr-1"></i>Pesanan lama ini belum memiliki titik navigasi.</p>
                    @endif
                </div>
            </section>

            @if($order->items->contains(fn ($item) => $item->review))
                <section class="rounded-2xl border border-slate-200 bg-white p-5">
                    <h2 class="font-black">Ulasan Pembeli</h2>
                    <div class="mt-4 space-y-4">
                        @foreach($order->items->whereNotNull('review') as $item)
                            <article class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                                <div class="flex items-start justify-between gap-3">
                                    <div><p class="font-bold text-slate-900">{{ $item->product_name }}</p><p class="mt-1 text-sm text-slate-600">{{ $item->review->comment ?: 'Pembeli memberikan penilaian tanpa komentar.' }}</p></div>
                                    <span class="whitespace-nowrap text-xs text-accent-yellow">@for($star = 1; $star <= 5; $star++)<i class="{{ $star <= $item->review->rating ? 'fas' : 'far' }} fa-star"></i>@endfor</span>
                                </div>
                                <form method="POST" action="{{ route('admin.reviews.reply', $item->review) }}" class="mt-4" data-confirm="Balasan toko akan tampil secara publik pada detail produk." data-confirm-title="Simpan balasan ulasan?" data-confirm-button="Ya, simpan balasan">
                                    @csrf
                                    <textarea name="admin_reply" rows="3" required placeholder="Tulis balasan resmi toko" class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm">{{ $item->review->admin_reply }}</textarea>
                                    <button class="mt-2 rounded-xl bg-primary px-4 py-2.5 text-sm font-bold text-white hover:bg-secondary">{{ $item->review->admin_reply ? 'Perbarui Balasan' : 'Balas Ulasan' }}</button>
                                </form>
                            </article>
                        @endforeach
                    </div>
                </section>
            @endif

            @if($order->dispatchProofs->isNotEmpty())<x-shipping-proof :proofs="$order->dispatchProofs" title="Bukti Paket Mulai Diantar" stage="dispatch" />@endif
            @if($order->deliveryProofs->isNotEmpty())<x-shipping-proof :proofs="$order->deliveryProofs" title="Bukti Paket Tiba" stage="delivery" />@endif

            <section class="rounded-2xl border border-slate-200 bg-white p-5">
                <h2 class="font-black">Histori Internal</h2>
                <ol class="mt-4 space-y-4">
                    @foreach($order->histories as $history)
                        <li class="border-l-2 border-primary pl-4 text-sm">
                            <x-status-badge :status="$history->to_status" />
                            <p class="mt-2 text-slate-500">{{ $history->created_at->format('d/m/Y H:i') }} · {{ $history->actor?->name ?? 'Sistem' }}</p>
                            @if($history->note)<p class="mt-1 text-slate-700">{{ $history->note }}</p>@endif
                        </li>
                    @endforeach
                </ol>
            </section>
        </div>

        <aside class="h-fit rounded-2xl bg-gradient-to-br from-primary to-secondary p-6 text-white">
            <h2 class="text-lg font-black">Aksi Pesanan</h2>
            <p class="mt-1 text-sm text-white/85">Tiap perubahan disimpan ke histori internal.</p>

            <div class="mt-5 space-y-3 rounded-xl border border-white/20 bg-primary-dark/60 p-4 text-sm">
                <div class="flex flex-wrap items-center justify-between gap-2"><span>Status pesanan</span><x-status-badge :status="$order->status" /></div>
                <div class="flex flex-wrap items-center justify-between gap-2"><span>Status pembayaran</span><x-status-badge :status="$order->payment_status" type="payment" /></div>
                <div class="flex items-center justify-between gap-3"><span>Kanal</span><strong class="text-right">{{ $order->gateway_payment_method ?: $order->payment_method->label() }}</strong></div>
            </div>

            @if($order->status === App\Enums\OrderStatus::PendingPayment)
                <div class="mt-4 rounded-xl bg-primary-dark/75 p-4 text-sm">
                    <p>Gateway: <strong>{{ ucfirst($order->payment_gateway) }}</strong></p>
                    <p class="mt-1 break-all text-xs text-white/75">{{ $order->payment_reference }}</p>
                </div>
                @if($order->payment_gateway === 'placeholder')
                    <form method="POST" action="{{ route('admin.orders.confirm-payment', $order) }}" class="mt-4" data-confirm="Pembayaran akan ditandai lunas dan stok produk langsung dikurangi." data-confirm-title="Konfirmasi pembayaran?" data-confirm-icon="warning" data-confirm-button="Ya, konfirmasi">@csrf<button class="w-full rounded-xl bg-primary px-4 py-3 font-bold text-white">Konfirmasi Pembayaran Internal</button></form>
                @else
                    <div class="mt-4 rounded-xl border border-white/25 bg-white/10 p-4 text-xs leading-5 text-white/85">Pembayaran digital dikonfirmasi otomatis melalui webhook bertanda tangan. Admin tidak perlu menandai lunas secara manual.</div>
                @endif
            @elseif($order->status === App\Enums\OrderStatus::Processing)
                <form method="POST" action="{{ route('admin.orders.update-status', $order) }}" class="mt-5 space-y-3" data-confirm="Pesanan akan ditandai selesai diproses dan siap dikirim." data-confirm-title="Tandai siap dikirim?" data-confirm-button="Ya, siap dikirim">
                    @csrf @method('PATCH')
                    <input type="hidden" name="status" value="ready">
                    <textarea name="note" rows="2" placeholder="Catatan persiapan (opsional)" class="w-full rounded-xl border border-white/25 bg-primary-dark/70 px-4 py-3 text-sm text-white placeholder:text-white/60"></textarea>
                    <p class="text-xs leading-5 text-white/80">Tahap ini hanya penanda bahwa pesanan siap diserahkan kepada kurir.</p>
                    <button class="w-full rounded-xl bg-accent-yellow px-4 py-3 font-bold text-slate-900">Tandai Siap Dikirim</button>
                </form>
            @elseif($order->status === App\Enums\OrderStatus::Ready)
                <form x-data="{ proofPreviews: [], proofOpen: null, setProofs(files) { this.proofPreviews.forEach(url => URL.revokeObjectURL(url)); this.proofPreviews = Array.from(files).slice(0, 5).map(file => URL.createObjectURL(file)); } }" method="POST" action="{{ route('admin.orders.update-status', $order) }}" enctype="multipart/form-data" class="mt-5 space-y-3" data-confirm="Foto akan menjadi bukti paket mulai diantar dan terlihat oleh pembeli." data-confirm-title="Kurir mulai mengantar?" data-confirm-button="Ya, mulai antar">
                    @csrf @method('PATCH')
                    <input type="hidden" name="status" value="out_for_delivery">
                    <label class="block text-sm font-bold">Foto bukti mulai diantar <span class="font-normal text-white/75">(maks. 5)</span>
                        <input type="file" name="dispatch_proofs[]" accept="image/png,image/jpeg,image/webp" multiple required @change="setProofs($event.target.files)" class="mt-2 block w-full rounded-xl border border-white/25 bg-primary-dark/70 p-2 text-xs text-white">
                    </label>
                    <div x-cloak x-show="proofPreviews.length" class="grid grid-cols-2 gap-2"><template x-for="(preview, index) in proofPreviews" :key="preview"><button type="button" @click="proofOpen = preview" class="overflow-hidden rounded-xl bg-primary-dark" :aria-label="'Perbesar pratinjau pengiriman ' + (index + 1)"><img :src="preview" :alt="'Pratinjau pengiriman ' + (index + 1)" class="h-28 w-full object-contain"></button></template></div>
                    <div x-cloak x-show="proofOpen" x-transition.opacity @keydown.escape.window="proofOpen = null" class="fixed inset-0 z-[90] grid place-items-center bg-slate-950/90 p-4"><button type="button" @click="proofOpen = null" class="absolute right-5 top-5 grid h-11 w-11 place-items-center rounded-full bg-white text-slate-900"><i class="fas fa-xmark"></i></button><img :src="proofOpen" alt="Pratinjau bukti pengiriman" class="max-h-[90vh] max-w-full rounded-2xl object-contain"></div>
                    <textarea name="note" rows="2" placeholder="Catatan pengiriman (opsional)" class="w-full rounded-xl border border-white/25 bg-primary-dark/70 px-4 py-3 text-sm text-white placeholder:text-white/60"></textarea>
                    <button class="w-full rounded-xl bg-accent-yellow px-4 py-3 font-bold text-slate-900">Simpan Bukti & Mulai Mengantar</button>
                </form>
            @elseif($order->status === App\Enums\OrderStatus::OutForDelivery)
                <form x-data="{ proofPreviews: [], proofOpen: null, setProofs(files) { this.proofPreviews.forEach(url => URL.revokeObjectURL(url)); this.proofPreviews = Array.from(files).slice(0, 5).map(file => URL.createObjectURL(file)); } }" method="POST" action="{{ route('admin.orders.mark-delivered', $order) }}" enctype="multipart/form-data" class="mt-5 space-y-3" data-confirm="Foto akan menjadi bukti resmi paket telah tiba dan terlihat oleh pembeli." data-confirm-title="Unggah bukti paket tiba?" data-confirm-button="Ya, unggah bukti">
                    @csrf
                    <label class="block text-sm font-bold">Foto bukti paket tiba <span class="font-normal text-white/75">(maks. 5)</span>
                        <input type="file" name="delivery_proofs[]" accept="image/png,image/jpeg,image/webp" multiple required @change="setProofs($event.target.files)" class="mt-2 block w-full rounded-xl border border-white/25 bg-primary-dark/70 p-2 text-xs text-white">
                    </label>
                    <div x-cloak x-show="proofPreviews.length" class="grid grid-cols-2 gap-2"><template x-for="(preview, index) in proofPreviews" :key="preview"><button type="button" @click="proofOpen = preview" class="overflow-hidden rounded-xl bg-primary-dark" :aria-label="'Perbesar pratinjau bukti tiba ' + (index + 1)"><img :src="preview" :alt="'Pratinjau bukti tiba ' + (index + 1)" class="h-28 w-full object-contain"></button></template></div>
                    <div x-cloak x-show="proofOpen" x-transition.opacity @keydown.escape.window="proofOpen = null" class="fixed inset-0 z-[90] grid place-items-center bg-slate-950/90 p-4"><button type="button" @click="proofOpen = null" class="absolute right-5 top-5 grid h-11 w-11 place-items-center rounded-full bg-white text-slate-900"><i class="fas fa-xmark"></i></button><img :src="proofOpen" alt="Pratinjau bukti paket tiba" class="max-h-[90vh] max-w-full rounded-2xl object-contain"></div>
                    <textarea name="delivery_note" rows="3" placeholder="Catatan penyerahan (opsional)" class="w-full rounded-xl border border-white/25 bg-primary-dark/70 px-4 py-3 text-sm text-white placeholder:text-white/60"></textarea>
                    <button class="w-full rounded-xl bg-accent-yellow px-4 py-3 font-bold text-slate-900">Unggah Bukti Tiba</button>
                </form>
            @elseif($order->status === App\Enums\OrderStatus::Delivered)
                <div class="mt-5 rounded-xl bg-emerald-900/50 p-4 text-sm text-emerald-50">Bukti tiba sudah diunggah. Menunggu pembeli menekan konfirmasi barang diterima.</div>
            @elseif($order->status === App\Enums\OrderStatus::Completed)
                <div class="mt-5 rounded-xl bg-emerald-900/50 p-4 text-sm text-emerald-50">Pesanan telah dikonfirmasi selesai.</div>
            @elseif($order->status === App\Enums\OrderStatus::Cancelled)
                <div class="mt-5 rounded-xl bg-red-950/45 p-4 text-sm text-red-50">Pesanan telah dibatalkan dan tidak dapat diproses lebih lanjut.</div>
            @endif
        </aside>
    </div>
</x-layouts.app>
