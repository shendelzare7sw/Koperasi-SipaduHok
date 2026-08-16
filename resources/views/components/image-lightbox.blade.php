@props([
    'src',
    'alt' => 'Pratinjau gambar',
    'imageClass' => 'w-full rounded-xl object-cover',
])

<div x-data="{ imageOpen: false }">
    <button type="button" @click="imageOpen = true" class="group relative block w-full cursor-zoom-in overflow-hidden rounded-xl" aria-label="Perbesar {{ $alt }}">
        <img src="{{ $src }}" alt="{{ $alt }}" class="{{ $imageClass }}">
        <span class="absolute inset-0 grid place-items-center bg-slate-950/0 text-white opacity-0 transition group-hover:bg-slate-950/35 group-hover:opacity-100"><i class="fas fa-magnifying-glass-plus text-xl" aria-hidden="true"></i></span>
    </button>
    <div x-cloak x-show="imageOpen" x-transition.opacity @keydown.escape.window="imageOpen = false" class="fixed inset-0 z-[90] grid place-items-center bg-slate-950/90 p-4" role="dialog" aria-modal="true">
        <button type="button" @click="imageOpen = false" class="absolute right-5 top-5 grid h-11 w-11 place-items-center rounded-full bg-white text-slate-900 shadow-xl" aria-label="Tutup gambar"><i class="fas fa-xmark" aria-hidden="true"></i></button>
        <img src="{{ $src }}" alt="{{ $alt }}" class="max-h-[90vh] max-w-full rounded-2xl object-contain shadow-2xl">
    </div>
</div>
