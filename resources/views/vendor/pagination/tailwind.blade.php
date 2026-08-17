@if ($paginator->hasPages())
    <nav data-pagination role="navigation" aria-label="Navigasi halaman" class="rounded-2xl border border-slate-200 bg-white p-3 shadow-sm sm:p-4">
        <div class="sm:hidden">
            <div class="flex items-center gap-2">
                @if ($paginator->onFirstPage())
                    <span aria-disabled="true" class="grid h-10 w-10 shrink-0 cursor-not-allowed place-items-center rounded-xl bg-slate-100 text-slate-300"><i class="fas fa-chevron-left" aria-hidden="true"></i><span class="sr-only">Halaman sebelumnya</span></span>
                @else
                    <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="grid h-10 w-10 shrink-0 place-items-center rounded-xl border border-slate-200 text-primary transition hover:border-primary hover:bg-blue-50" aria-label="Halaman sebelumnya"><i class="fas fa-chevron-left" aria-hidden="true"></i></a>
                @endif

                <div class="min-w-0 flex-1 overflow-x-auto [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                    <div class="flex w-max min-w-full items-center justify-center gap-1.5 px-1">
                        @foreach ($elements as $element)
                            @if (is_string($element))
                                <span class="grid h-9 min-w-6 place-items-center text-xs font-bold text-slate-400">{{ $element }}</span>
                            @endif

                            @if (is_array($element))
                                @foreach ($element as $page => $url)
                                    @if ($page == $paginator->currentPage())
                                        <span data-mobile-current-page="{{ $page }}" aria-current="page" class="grid h-9 min-w-9 place-items-center rounded-lg bg-primary px-2 text-sm font-black text-white shadow-sm">{{ $page }}</span>
                                    @else
                                        <a data-mobile-page-link="{{ $page }}" href="{{ $url }}" class="grid h-9 min-w-9 place-items-center rounded-lg border border-slate-200 bg-white px-2 text-sm font-bold text-slate-600 transition hover:border-primary hover:bg-blue-50 hover:text-primary" aria-label="Buka halaman {{ $page }}">{{ $page }}</a>
                                    @endif
                                @endforeach
                            @endif
                        @endforeach
                    </div>
                </div>

                @if ($paginator->hasMorePages())
                    <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-primary text-white transition hover:bg-primary-dark" aria-label="Halaman berikutnya"><i class="fas fa-chevron-right" aria-hidden="true"></i></a>
                @else
                    <span aria-disabled="true" class="grid h-10 w-10 shrink-0 cursor-not-allowed place-items-center rounded-xl bg-slate-100 text-slate-300"><i class="fas fa-chevron-right" aria-hidden="true"></i><span class="sr-only">Halaman berikutnya</span></span>
                @endif
            </div>

            <p class="mt-2 text-center text-xs font-bold text-slate-500">
                Halaman <span class="font-black text-slate-800">{{ $paginator->currentPage() }}</span> dari {{ $paginator->lastPage() }}
            </p>
        </div>

        <div class="hidden gap-4 sm:flex sm:flex-col sm:items-center sm:justify-between xl:flex-row">
            <p class="text-sm text-slate-500">
                Menampilkan <span class="font-black text-slate-800">{{ $paginator->firstItem() ?? 0 }}–{{ $paginator->lastItem() ?? 0 }}</span>
                dari <span class="font-black text-slate-800">{{ $paginator->total() }}</span> data
            </p>

            <div class="flex flex-wrap items-center justify-center gap-1.5">
                @if ($paginator->onFirstPage())
                    <span aria-disabled="true" class="grid h-10 w-10 cursor-not-allowed place-items-center rounded-xl bg-slate-100 text-slate-300"><i class="fas fa-chevron-left text-xs" aria-hidden="true"></i></span>
                @else
                    <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="grid h-10 w-10 place-items-center rounded-xl border border-slate-200 text-primary transition hover:border-primary hover:bg-blue-50" aria-label="Halaman sebelumnya"><i class="fas fa-chevron-left text-xs" aria-hidden="true"></i></a>
                @endif

                @foreach ($elements as $element)
                    @if (is_string($element))
                        <span class="grid h-10 min-w-8 place-items-center text-sm font-bold text-slate-400">{{ $element }}</span>
                    @endif

                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            @if ($page == $paginator->currentPage())
                                <span aria-current="page" class="grid h-10 min-w-10 place-items-center rounded-xl bg-primary px-2 text-sm font-black text-white shadow-sm">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}" class="grid h-10 min-w-10 place-items-center rounded-xl border border-slate-200 bg-white px-2 text-sm font-bold text-slate-600 transition hover:border-primary hover:bg-blue-50 hover:text-primary" aria-label="Buka halaman {{ $page }}">{{ $page }}</a>
                            @endif
                        @endforeach
                    @endif
                @endforeach

                @if ($paginator->hasMorePages())
                    <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="grid h-10 w-10 place-items-center rounded-xl bg-primary text-white transition hover:bg-primary-dark" aria-label="Halaman berikutnya"><i class="fas fa-chevron-right text-xs" aria-hidden="true"></i></a>
                @else
                    <span aria-disabled="true" class="grid h-10 w-10 cursor-not-allowed place-items-center rounded-xl bg-slate-100 text-slate-300"><i class="fas fa-chevron-right text-xs" aria-hidden="true"></i></span>
                @endif
            </div>
        </div>
    </nav>
@endif
