@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Navigasi halaman" class="flex items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-white p-3 shadow-sm">
        @if ($paginator->onFirstPage())
            <span aria-disabled="true" class="inline-flex cursor-not-allowed items-center gap-2 rounded-xl bg-slate-100 px-4 py-2.5 text-sm font-bold text-slate-300"><i class="fas fa-chevron-left text-xs" aria-hidden="true"></i>Sebelumnya</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-bold text-primary hover:border-primary hover:bg-blue-50"><i class="fas fa-chevron-left text-xs" aria-hidden="true"></i>Sebelumnya</a>
        @endif

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="inline-flex items-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-sm font-bold text-white hover:bg-primary-dark">Berikutnya<i class="fas fa-chevron-right text-xs" aria-hidden="true"></i></a>
        @else
            <span aria-disabled="true" class="inline-flex cursor-not-allowed items-center gap-2 rounded-xl bg-slate-100 px-4 py-2.5 text-sm font-bold text-slate-300">Berikutnya<i class="fas fa-chevron-right text-xs" aria-hidden="true"></i></span>
        @endif
    </nav>
@endif
