<x-layouts.app title="Checkout - Koperasi Sipaduhok">
    @php
        $primaryAddress = $addresses->firstWhere('is_primary', true) ?? $addresses->first();
        $selectedAddressId = (string) old('address_id', $primaryAddress?->id);
        $checkoutItemIds = $items->pluck('id')->all();
    @endphp

    <div>
        <p class="text-sm font-extrabold uppercase tracking-widest text-secondary">Selesaikan Pesanan</p>
        <h1 class="mt-1 text-3xl font-black text-slate-900">Checkout</h1>
        <p class="mt-1 text-slate-500">Pilih alamat tersimpan, lengkapi data siswa, lalu periksa ringkasan pembayaran.</p>
    </div>

    <form method="POST" action="{{ route('checkout.store') }}" class="mt-6 grid gap-6 lg:grid-cols-[minmax(0,1fr)_360px]" x-data="{ selectedAddress: @js($selectedAddressId) }" data-confirm="Periksa kembali alamat, data siswa, kurir, dan metode pembayaran." data-confirm-title="Buat pesanan ini?" data-confirm-button="Ya, buat pesanan">
        @csrf
        @foreach($items as $item)
            <input type="hidden" name="cart_item_ids[]" value="{{ $item->id }}">
        @endforeach

        <div class="space-y-6">
            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <div class="flex items-center gap-3"><span class="grid h-10 w-10 place-items-center rounded-xl bg-blue-50 text-primary"><i class="fas fa-map-location-dot" aria-hidden="true"></i></span><h2 class="text-lg font-black text-slate-900">Alamat Pengiriman</h2></div>
                        <p class="mt-2 text-sm text-slate-500">Pesanan dikirim oleh satu Kurir Koperasi ke alamat yang dipilih.</p>
                    </div>
                    <a href="{{ route('account.addresses.index', ['checkout_items' => $checkoutItemIds]) }}" class="inline-flex shrink-0 items-center gap-2 text-sm font-bold text-primary hover:text-secondary"><i class="fas fa-gear" aria-hidden="true"></i>Kelola Alamat</a>
                </div>

                <div class="mt-5 grid gap-3 sm:grid-cols-2">
                    @foreach($addresses as $address)
                        <label class="relative cursor-pointer rounded-2xl border p-4 transition" :class="selectedAddress === '{{ $address->id }}' ? 'border-primary bg-blue-50 ring-2 ring-primary/10' : 'border-slate-200 hover:border-primary/40'">
                            <input type="radio" name="address_id" value="{{ $address->id }}" x-model="selectedAddress" required class="sr-only">
                            <span class="flex items-start justify-between gap-3">
                                <span>
                                    <span class="font-extrabold text-slate-900">{{ $address->label }}</span>
                                    @if($address->is_primary)<span class="ml-1 rounded-full bg-primary px-2 py-0.5 text-[9px] font-black uppercase tracking-wide text-white">Utama</span>@endif
                                </span>
                                <span class="grid h-6 w-6 place-items-center rounded-full border-2" :class="selectedAddress === '{{ $address->id }}' ? 'border-primary bg-primary text-white' : 'border-slate-300 text-transparent'"><i class="fas fa-check text-[10px]" aria-hidden="true"></i></span>
                            </span>
                            <span class="mt-3 block text-sm font-bold text-slate-700">{{ $address->recipient_name }} · {{ $address->phone }}</span>
                            <span class="mt-2 block whitespace-pre-line text-xs leading-5 text-slate-500">{{ $address->formattedAddress() }}</span>
                        </label>
                    @endforeach
                </div>
            </section>

            <section class="grid gap-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:grid-cols-2 sm:p-6">
                <div class="sm:col-span-2">
                    <div class="flex items-center gap-3"><span class="grid h-10 w-10 place-items-center rounded-xl bg-blue-50 text-primary"><i class="fas fa-graduation-cap" aria-hidden="true"></i></span><h2 class="text-lg font-black text-slate-900">Data Siswa</h2></div>
                    <p class="mt-2 text-sm text-slate-500">Data ini membantu koperasi mengidentifikasi pesanan dalam ekosistem sekolah.</p>
                </div>
                <label class="text-sm font-semibold">Nama siswa
                    <input name="student_name" value="{{ old('student_name') }}" required maxlength="255" autocomplete="name" class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20">
                </label>
                <label class="text-sm font-semibold">Kelas
                    <input name="class_name" value="{{ old('class_name') }}" placeholder="Contoh: VIII-A" required maxlength="100" class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20">
                </label>
            </section>

            <section class="space-y-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                <div class="flex items-center gap-3"><span class="grid h-10 w-10 place-items-center rounded-xl bg-blue-50 text-primary"><i class="fas fa-truck" aria-hidden="true"></i></span><h2 class="text-lg font-black text-slate-900">Pengiriman</h2></div>
                @if($courier)
                    <div class="flex items-start justify-between gap-4 rounded-xl border border-primary/20 bg-blue-50 p-4">
                        <div><p class="font-bold text-slate-900">{{ $courier->name }}</p><p class="mt-1 text-sm text-slate-600">Kurir internal resmi Koperasi Sipaduhok{{ $courier->estimate ? ' · '.$courier->estimate : '' }}</p></div>
                        <p class="shrink-0 font-black text-primary">Rp {{ number_format($courier->fee, 0, ',', '.') }}</p>
                    </div>
                @else
                    <div class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">Kurir Koperasi sedang dinonaktifkan admin. Checkout belum tersedia.</div>
                @endif
                <label class="block text-sm font-semibold">Catatan pesanan (opsional)
                    <textarea name="notes" rows="3" maxlength="1000" placeholder="Contoh: antar setelah jam sekolah" class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20">{{ old('notes') }}</textarea>
                </label>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                <div class="flex items-center gap-3"><span class="grid h-10 w-10 place-items-center rounded-xl bg-blue-50 text-primary"><i class="fas fa-wallet" aria-hidden="true"></i></span><h2 class="text-lg font-black text-slate-900">Metode Pembayaran</h2></div>
                <div class="mt-4 grid gap-3 sm:grid-cols-2">
                    @foreach($paymentMethods as $method)
                        <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-slate-200 p-4 transition hover:border-primary/40"><input type="radio" name="payment_method" value="{{ $method->value }}" required @checked(old('payment_method', 'qris') === $method->value) class="text-primary focus:ring-primary"> <span class="font-bold">{{ $method->label() }}</span></label>
                    @endforeach
                </div>
                <p class="mt-3 text-xs leading-5 text-slate-500">Pembayaran diproses langsung atas nama Koperasi Sipaduhok. Tidak ada saldo tertahan atau escrow.</p>
            </section>
        </div>

        <aside class="h-fit rounded-2xl bg-gradient-to-br from-primary to-secondary p-6 text-white shadow-xl shadow-primary/20 lg:sticky lg:top-6">
            <h2 class="text-lg font-black">Ringkasan Pesanan</h2>
            <div class="mt-4 space-y-3 text-sm">
                @foreach($items as $item)
                    <div class="flex justify-between gap-4"><span class="text-blue-50">{{ $item->product->name }} × {{ $item->quantity }}</span><span class="shrink-0">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</span></div>
                @endforeach
                <div class="flex justify-between border-t border-white/20 pt-3"><span>Subtotal</span><span>Rp {{ number_format($subtotal, 0, ',', '.') }}</span></div>
                <div class="flex justify-between"><span>Kurir Koperasi</span><span>{{ $courier ? 'Rp '.number_format($courier->fee, 0, ',', '.') : '-' }}</span></div>
                <div class="flex justify-between border-t border-white/20 pt-3 text-lg font-black"><span>Total</span><span>Rp {{ number_format($subtotal + ($courier?->fee ?? 0), 0, ',', '.') }}</span></div>
            </div>
            <button @disabled(!$courier) class="mt-6 w-full rounded-xl bg-white px-4 py-3 font-bold text-primary transition hover:bg-accent-yellow hover:text-slate-900 disabled:cursor-not-allowed disabled:opacity-40"><i class="fas fa-lock mr-2" aria-hidden="true"></i>Buat Pesanan</button>
            <p class="mt-3 text-center text-[11px] leading-5 text-blue-100">Alamat dan tarif kurir disimpan sebagai snapshot pada invoice.</p>
        </aside>
    </form>
</x-layouts.app>
