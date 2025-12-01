@if ($paginator->hasPages())
<div class="dataTables_info" id="dataTableBuilder_info" role="status" aria-live="polite" style="margin-top: 15px;">
    Showing {{ $paginator->count() }} of {{ $paginator->total() }} entries
</div>

<div class="dataTables_paginate paging_simple_numbers mt-2" id="dataTableBuilder_paginate">
    <ul class="pagination justify-content-end">

        {{-- Previous --}}
        @if ($paginator->onFirstPage())
        <li class="paginate_button page-item previous disabled">
            <a href="javascript:void(0)" class="page-link">Previous</a>
        </li>
        @else
        <li class="paginate_button page-item previous">
            <a href="{{ $paginator->previousPageUrl() }}" class="page-link">Previous</a>
        </li>
        @endif

        {{-- Page Number Links --}}
        @php
        $total = $paginator->lastPage();
        $current = $paginator->currentPage();
        $start = max(1, $current - 2);
        $end = min($total, $current + 4);
        @endphp

        {{-- Always show first page --}}
        @if ($start > 1)
        <li class="paginate_button page-item">
            <a href="{{ $paginator->url(1) }}" class="page-link">1</a>
        </li>
        @if ($start > 2)
        <li class="paginate_button page-item disabled"><a class="page-link">...</a></li>
        @endif
        @endif

        {{-- Dynamic windowed range --}}
        @for ($i = $start; $i <= $end; $i++)
            @if ($i==$current)
            <li class="paginate_button page-item active">
            <a href="{{ $paginator->appends(request()->query())->url($i) }}" class="page-link">{{ $i }}</a>

            </li>
            @else
            <li class="paginate_button page-item">
                <a href="{{ $paginator->appends(request()->query())->url($i) }}" class="page-link">{{ $i }}</a>

            </li>
            @endif
            @endfor

            {{-- Always show last page --}}
            @if ($end < $total)
                @if ($end < $total - 1)
                <li class="paginate_button page-item disabled"><a class="page-link">...</a></li>
                @endif
                <li class="paginate_button page-item">
                    <a href="{{ $paginator->url($total) }}" class="page-link">{{ $total }}</a>
                </li>
                @endif

                {{-- Next --}}
                @if ($paginator->hasMorePages())
                <li class="paginate_button page-item next">
                    <a href="{{ $paginator->nextPageUrl() }}" class="page-link">Next</a>
                </li>
                @else
                <li class="paginate_button page-item next disabled">
                    <a href="javascript:void(0)" class="page-link">Next</a>
                </li>
                @endif

    </ul>
</div>
@endif