@php
    $categoryIcons = [
        'buku' => 'fa-book-open',
        'alat_tulis' => 'fa-pen-ruler',
        'atribut_sekolah' => 'fa-shirt',
        'lainnya' => 'fa-box-open',
    ];
@endphp

<form method="GET" action="{{ route('catalog.index') }}" class="space-y-6">
    @if(request()->filled('search'))
        <input type="hidden" name="search" value="{{ request('search') }}">
    @endif
    @if(request()->filled('sort'))
        <input type="hidden" name="sort" value="{{ request('sort') }}">
    @endif

    <fieldset>
        <legend class="mb-3 text-xs font-black uppercase tracking-widest text-slate-500">Kategori</legend>
        <div class="space-y-1.5">
            <label class="flex cursor-pointer items-center gap-3 rounded-xl px-3 py-2.5 transition hover:bg-blue-50">
                <input type="radio" name="category" value="" @checked(! request()->filled('category')) class="h-4 w-4 border-slate-300 text-primary focus:ring-primary/30">
                <span class="grid h-8 w-8 place-items-center rounded-full bg-slate-100 text-slate-500"><i class="fas fa-border-all text-xs" aria-hidden="true"></i></span>
                <span class="flex-1 text-sm font-bold text-slate-700">Semua kategori</span>
                <span class="text-xs font-bold text-slate-400">{{ $categoryCounts->sum() }}</span>
            </label>
            @foreach($categories as $value => $label)
                <label class="flex cursor-pointer items-center gap-3 rounded-xl px-3 py-2.5 transition hover:bg-blue-50">
                    <input type="radio" name="category" value="{{ $value }}" @checked(request('category') === $value) class="h-4 w-4 border-slate-300 text-primary focus:ring-primary/30">
                    <span class="grid h-8 w-8 place-items-center rounded-full bg-blue-50 text-primary"><i class="fas {{ $categoryIcons[$value] }} text-xs" aria-hidden="true"></i></span>
                    <span class="flex-1 text-sm font-bold text-slate-700">{{ $label }}</span>
                    <span class="text-xs font-bold text-slate-400">{{ $categoryCounts->get($value, 0) }}</span>
                </label>
            @endforeach
        </div>
    </fieldset>

    <fieldset class="border-t border-slate-100 pt-5">
        <legend class="mb-3 text-xs font-black uppercase tracking-widest text-slate-500">Rentang harga</legend>
        <div class="space-y-3">
            <label class="relative block"><span class="absolute left-3 top-1/2 -translate-y-1/2 text-xs font-bold text-slate-400">Rp</span><input name="min_price" type="text" inputmode="numeric" pattern="[0-9.]*" value="{{ request('min_price') }}" placeholder="Minimum" data-rupiah-input class="w-full rounded-xl border border-slate-300 py-2.5 pl-9 pr-3 text-sm focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"></label>
            <label class="relative block"><span class="absolute left-3 top-1/2 -translate-y-1/2 text-xs font-bold text-slate-400">Rp</span><input name="max_price" type="text" inputmode="numeric" pattern="[0-9.]*" value="{{ request('max_price') }}" placeholder="Maksimum" data-rupiah-input class="w-full rounded-xl border border-slate-300 py-2.5 pl-9 pr-3 text-sm focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"></label>
        </div>
    </fieldset>

    <fieldset class="border-t border-slate-100 pt-5">
        <legend class="mb-3 text-xs font-black uppercase tracking-widest text-slate-500">Rating minimum</legend>
        <div class="space-y-1">
            @foreach([4, 3, 2, 1] as $rating)
                <label class="flex cursor-pointer items-center gap-3 rounded-xl px-3 py-2 transition hover:bg-amber-50">
                    <input type="radio" name="rating" value="{{ $rating }}" @checked((int) request('rating') === $rating) class="h-4 w-4 border-slate-300 text-primary focus:ring-primary/30">
                    <span class="flex gap-0.5 text-xs text-amber-400">@for($star = 1; $star <= 5; $star++)<i class="{{ $star <= $rating ? 'fas' : 'far' }} fa-star" aria-hidden="true"></i>@endfor</span>
                    <span class="text-xs text-slate-500">ke atas</span>
                </label>
            @endforeach
            <label class="flex cursor-pointer items-center gap-3 rounded-xl px-3 py-2 transition hover:bg-slate-50"><input type="radio" name="rating" value="" @checked(! request()->filled('rating')) class="h-4 w-4 border-slate-300 text-primary focus:ring-primary/30"><span class="text-sm font-bold text-slate-600">Semua rating</span></label>
        </div>
    </fieldset>

    <div class="space-y-2 border-t border-slate-100 pt-5">
        <button class="w-full rounded-xl bg-primary px-5 py-3 font-bold text-white transition hover:bg-primary-dark"><i class="fas fa-check mr-2" aria-hidden="true"></i>Terapkan Filter</button>
        <a href="{{ route('catalog.index', request()->filled('search') ? ['search' => request('search')] : []) }}" class="block rounded-xl px-5 py-2.5 text-center text-sm font-bold text-slate-500 transition hover:bg-red-50 hover:text-red-600">Reset filter</a>
    </div>
</form>
