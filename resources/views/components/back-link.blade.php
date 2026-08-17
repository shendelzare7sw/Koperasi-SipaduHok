@props(['href', 'label' => 'Kembali'])

<a href="{{ $href }}" data-back-link {{ $attributes->class(['inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-bold text-slate-600 shadow-sm transition hover:border-primary hover:bg-blue-50 hover:text-primary']) }}>
    <i class="fas fa-arrow-left" aria-hidden="true"></i>
    <span>{{ $label }}</span>
</a>
