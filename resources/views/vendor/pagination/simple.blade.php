@if ($paginator->hasPages())
    <nav>
        <div>
            @if ($paginator->onFirstPage())
                <span class="muted"><i class="bi bi-chevron-left"></i> Previous</span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev"><i class="bi bi-chevron-left"></i> Previous</a>
            @endif

            <span>Page {{ $paginator->currentPage() }} of {{ $paginator->lastPage() }}</span>

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next">Next <i class="bi bi-chevron-right"></i></a>
            @else
                <span class="muted">Next <i class="bi bi-chevron-right"></i></span>
            @endif
        </div>
    </nav>
@endif
