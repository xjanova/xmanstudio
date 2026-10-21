{{-- Pagination in the community's own style (Laravel's default needs Tailwind) --}}
@if ($paginator->hasPages())
    <nav class="gs-pages" role="navigation" aria-label="เปลี่ยนหน้า">
        @if ($paginator->onFirstPage())
            <span class="off">‹ ก่อนหน้า</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev">‹ ก่อนหน้า</a>
        @endif
        <span class="gs-muted">หน้า {{ $paginator->currentPage() }} / {{ $paginator->lastPage() }}</span>
        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next">ถัดไป ›</a>
        @else
            <span class="off">ถัดไป ›</span>
        @endif
    </nav>
@endif
