<x-layouts.app :title="$title.' - Toko Sipaduhok'">
    <div class="mx-auto max-w-5xl">
        <section class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-primary to-secondary px-6 py-10 text-white shadow-xl shadow-primary/15 sm:px-10">
            <div class="pointer-events-none absolute -right-14 -top-20 h-56 w-56 rounded-full bg-accent-yellow/20 blur-2xl"></div>
            <p class="relative text-sm font-extrabold uppercase tracking-widest text-accent-yellow">{{ $eyebrow }}</p>
            <h1 class="relative mt-2 text-3xl font-black sm:text-5xl">{{ $title }}</h1>
            <p class="relative mt-4 max-w-2xl text-sm leading-7 text-blue-50">Informasi resmi {{ $settings['legal_name'] }} untuk pembeli dan mitra layanan pembayaran.</p>
        </section>

        <div class="mt-8 grid gap-6 lg:grid-cols-[minmax(0,1fr)_300px]">
            <div class="space-y-5">
                @foreach($sections as $section)
                    <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                        <h2 class="text-xl font-black text-slate-900">{{ $section['title'] }}</h2>
                        @foreach($section['paragraphs'] ?? [] as $paragraph)<p class="mt-3 text-sm leading-7 text-slate-600">{{ $paragraph }}</p>@endforeach
                        @if(! empty($section['items']))<ul class="mt-4 space-y-3">@foreach($section['items'] as $item)<li class="flex gap-3 text-sm leading-6 text-slate-600"><i class="fas fa-circle-check mt-1 text-secondary" aria-hidden="true"></i><span>{{ $item }}</span></li>@endforeach</ul>@endif
                    </section>
                @endforeach
            </div>

            <aside class="h-fit rounded-2xl border border-primary/20 bg-blue-50 p-5 lg:sticky lg:top-6">
                <span class="grid h-11 w-11 place-items-center rounded-xl bg-primary text-white"><i class="fas fa-headset"></i></span>
                <h2 class="mt-4 font-black text-slate-900">Kontak Resmi</h2>
                <dl class="mt-4 space-y-4 text-sm">
                    <div><dt class="text-slate-500">Email</dt><dd class="mt-1 break-all font-bold text-primary">{{ $settings['support_email'] }}</dd></div>
                    @if($settings['phone'])<div><dt class="text-slate-500">Telepon</dt><dd class="mt-1 font-bold">{{ $settings['phone'] }}</dd></div>@endif
                    @if($settings['whatsapp'])<div><dt class="text-slate-500">WhatsApp</dt><dd class="mt-1 font-bold">{{ $settings['whatsapp'] }}</dd></div>@endif
                    <div><dt class="text-slate-500">Jam layanan</dt><dd class="mt-1 font-bold">{{ $settings['operating_hours'] }}</dd></div>
                    <div><dt class="text-slate-500">Alamat</dt><dd class="mt-1 whitespace-pre-line leading-6">{{ $settings['address'] }}</dd></div>
                </dl>
                <a href="mailto:{{ $settings['support_email'] }}" class="mt-5 flex items-center justify-center gap-2 rounded-xl bg-primary px-4 py-3 text-sm font-bold text-white hover:bg-secondary"><i class="fas fa-envelope"></i>Hubungi Toko</a>
            </aside>
        </div>
    </div>
</x-layouts.app>
