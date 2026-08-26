@if($paginator->hasPages())
    <nav class="pagination" aria-label="ページ移動">
        <span>{{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} / {{ $paginator->total() }}件</span>
        <div class="toolbar">
            @if($paginator->onFirstPage())<span class="button button-secondary button-small">前へ</span>@else<a class="button button-secondary button-small" href="{{ $paginator->previousPageUrl() }}">前へ</a>@endif
            @if($paginator->hasMorePages())<a class="button button-secondary button-small" href="{{ $paginator->nextPageUrl() }}">次へ</a>@else<span class="button button-secondary button-small">次へ</span>@endif
        </div>
    </nav>
@endif
