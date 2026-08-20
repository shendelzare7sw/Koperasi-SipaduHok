@props([
    'proofs',
    'title',
    'stage' => 'dispatch',
])

@php
    $proofs = collect($proofs);
    $note = $proofs->first(fn ($proof) => filled($proof->note))?->note;
    [$sectionColor, $headingColor, $noteColor, $icon] = match ($stage) {
        'delivery' => ['border-emerald-200 bg-emerald-50', 'text-emerald-900', 'text-emerald-800', 'fa-house-circle-check'],
        default => ['border-cyan-200 bg-cyan-50', 'text-cyan-900', 'text-cyan-800', 'fa-truck-fast'],
    };
@endphp

<section {{ $attributes->class("rounded-2xl border p-5 {$sectionColor}") }}>
    <div class="flex items-center gap-3">
        <span class="grid h-10 w-10 place-items-center rounded-xl bg-white/90 {{ $headingColor }} shadow-sm"><i class="fas {{ $icon }}" aria-hidden="true"></i></span>
        <div>
            <h2 class="font-black {{ $headingColor }}">{{ $title }}</h2>
            <p class="text-xs {{ $noteColor }}">Foto progres pengiriman dari admin toko.</p>
        </div>
    </div>
    <div class="mt-4 grid gap-3 sm:grid-cols-2">
        @foreach($proofs as $index => $proof)
            <x-image-lightbox :src="Storage::disk('public')->url($proof->path)" :alt="$title.' '.($index + 1)" image-class="h-56 w-full bg-white object-contain" />
        @endforeach
    </div>
    @if($note)<p class="mt-3 text-sm {{ $noteColor }}">{{ $note }}</p>@endif
</section>
