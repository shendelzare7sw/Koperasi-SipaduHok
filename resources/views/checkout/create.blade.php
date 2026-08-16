<x-layouts.app title="Checkout - Koperasi Sipaduhok">
    <h1 class="text-3xl font-black text-slate-900">Checkout</h1>
    <p class="mt-1 text-slate-500">Lengkapi data siswa dan alamat pengantaran.</p>

    <form method="POST" action="{{ route('checkout.store') }}" class="mt-6 grid gap-6 lg:grid-cols-[1fr_360px]">
        @csrf
        <div class="space-y-6">
            <section class="grid gap-4 rounded-2xl border border-slate-200 bg-white p-5 sm:grid-cols-2">
                <h2 class="text-lg font-black text-slate-900 sm:col-span-2">Data Pembeli</h2>
                <label class="text-sm font-semibold">Nama pemilik akun
                    <input name="buyer_name" value="{{ old('buyer_name', auth()->user()->name) }}" required class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3">
                </label>
                <label class="text-sm font-semibold">Nomor HP
                    <input name="phone" value="{{ old('phone', auth()->user()->phone) }}" required class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3">
                </label>
                <label class="text-sm font-semibold">Nama siswa
                    <input name="student_name" value="{{ old('student_name') }}" required class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3">
                </label>
                <label class="text-sm font-semibold">Kelas
                    <input name="class_name" value="{{ old('class_name') }}" placeholder="Contoh: VIII-A" required class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3">
                </label>
            </section>

            <section class="space-y-4 rounded-2xl border border-slate-200 bg-white p-5">
                <h2 class="text-lg font-black text-slate-900">Pengiriman</h2>
                @if($courier)
                    <div class="rounded-xl border border-orange-200 bg-orange-50 p-4">
                        <p class="font-bold text-slate-900">{{ $courier->name }}</p>
                        <p class="text-sm text-slate-600">Tarif flat Rp {{ number_format($courier->fee, 0, ',', '.') }}{{ $courier->estimate ? ' · '.$courier->estimate : '' }}</p>
                    </div>
                @else
                    <div class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">Kurir Koperasi sedang dinonaktifkan admin. Checkout belum tersedia.</div>
                @endif
                <label class="block text-sm font-semibold">Alamat lengkap pengantaran
                    <textarea name="delivery_address" rows="4" required class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3">{{ old('delivery_address') }}</textarea>
                </label>
                <label class="block text-sm font-semibold">Catatan pesanan (opsional)
                    <textarea name="notes" rows="2" class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3">{{ old('notes') }}</textarea>
                </label>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-5">
                <h2 class="text-lg font-black text-slate-900">Metode Pembayaran</h2>
                <div class="mt-4 grid gap-3 sm:grid-cols-2">
                    @foreach($paymentMethods as $method)
                        <label class="flex items-center gap-3 rounded-xl border border-slate-200 p-4"><input type="radio" name="payment_method" value="{{ $method->value }}" @checked(old('payment_method', 'qris') === $method->value)> <span class="font-bold">{{ $method->label() }}</span></label>
                    @endforeach
                </div>
                <p class="mt-3 text-xs text-slate-500">Saat ini masih mode placeholder; koneksi Midtrans/Tripay dipasang pada tahap integrasi.</p>
            </section>
        </div>

        <aside class="h-fit rounded-2xl bg-slate-900 p-6 text-white lg:sticky lg:top-6">
            <h2 class="text-lg font-black">Ringkasan</h2>
            <div class="mt-4 space-y-3 text-sm">
                @foreach($items as $item)
                    <div class="flex justify-between gap-4"><span class="text-slate-300">{{ $item->product->name }} × {{ $item->quantity }}</span><span>Rp {{ number_format($item->subtotal, 0, ',', '.') }}</span></div>
                @endforeach
                <div class="flex justify-between border-t border-slate-700 pt-3"><span>Subtotal</span><span>Rp {{ number_format($subtotal, 0, ',', '.') }}</span></div>
                <div class="flex justify-between"><span>Kurir</span><span>{{ $courier ? 'Rp '.number_format($courier->fee, 0, ',', '.') : '-' }}</span></div>
                <div class="flex justify-between border-t border-slate-700 pt-3 text-lg font-black"><span>Total</span><span>Rp {{ number_format($subtotal + ($courier?->fee ?? 0), 0, ',', '.') }}</span></div>
            </div>
            <button @disabled(!$courier) class="mt-6 w-full rounded-xl bg-orange-500 px-4 py-3 font-bold text-slate-950 disabled:opacity-40">Buat Pesanan</button>
        </aside>
    </form>
</x-layouts.app>
