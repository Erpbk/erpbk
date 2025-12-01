@props(['paginator', 'perPageOptions' => [20, 50, 100, 'all'], 'defaultPerPage' => 50])

@php
$currentPerPage = request()->input('per_page', $defaultPerPage);

// Handle 'all' and -1 options
if ($currentPerPage === 'all' || $currentPerPage === -1 || $currentPerPage === '-1') {
$isShowingAll = true;
$displayPerPage = 'all';
} else {
$currentPerPage = is_numeric($currentPerPage) ? (int) $currentPerPage : $defaultPerPage;
$currentPerPage = $currentPerPage > 0 ? $currentPerPage : $defaultPerPage;
$isShowingAll = $currentPerPage >= $paginator->total();
$displayPerPage = $isShowingAll ? 'all' : $currentPerPage;
}
@endphp

@if ($paginator->hasPages() || $paginator->total() > 0)
<div class="global-pagination-container">
    <!-- Pagination Controls in One Row -->
    <div class="pagination-single-row d-flex justify-content-between align-items-center mb-3">
        <!-- Left side: Records info -->
        <div class="records-info d-flex align-items-center gap-2">
            <span class="text-muted">
                Showing {{ $paginator->count() }} of {{ $paginator->total() }} entries
            </span>
            <div class="per-page-dropdown">
                <label for="perPageSelect" class="form-label me-2 mb-0">Show:</label>
                <select id="perPageSelect" class="form-select form-select-sm" style="width: auto; display: inline-block;">
                    @foreach($perPageOptions as $option)
                    @if($option === 'all' || $option === -1 || $option === '-1')
                    <option value="{{ $option }}" {{ ($displayPerPage === 'all' || $currentPerPage == -1 || $currentPerPage == '-1') ? 'selected' : '' }}>
                        All ({{ $paginator->total() }})
                    </option>
                    @else
                    <option value="{{ $option }}" {{ $currentPerPage == $option ? 'selected' : '' }}>
                        {{ $option }}
                    </option>
                    @endif
                    @endforeach
                </select>
            </div>
        </div>

        @if($paginator->hasPages())
        <div class="pagination-links">
            <nav aria-label="Pagination Navigation">
                <ul class="pagination mb-0">
                    {{-- Previous Page Link --}}
                    @if ($paginator->onFirstPage())
                    <li class="page-item disabled">
                        <span class="page-link">
                            <i class="ti ti-chevron-left"></i> Previous
                        </span>
                    </li>
                    @else
                    <li class="page-item">
                        <a class="page-link" href="{{ $paginator->appends(request()->query())->previousPageUrl() }}">
                            <i class="ti ti-chevron-left"></i> Previous
                        </a>
                    </li>
                    @endif

                    {{-- Pagination Elements --}}
                    @php
                    $total = $paginator->lastPage();
                    $current = $paginator->currentPage();
                    $start = max(1, $current - 2);
                    $end = min($total, $current + 2);
                    @endphp

                    {{-- First page --}}
                    @if ($start > 1)
                    <li class="page-item">
                        <a class="page-link" href="{{ $paginator->appends(request()->query())->url(1) }}">1</a>
                    </li>
                    @if ($start > 2)
                    <li class="page-item disabled">
                        <span class="page-link">...</span>
                    </li>
                    @endif
                    @endif

                    {{-- Page numbers --}}
                    @for ($i = $start; $i <= $end; $i++)
                        @if ($i==$current)
                        <li class="page-item active">
                        <span class="page-link">{{ $i }}</span>
                        </li>
                        @else
                        <li class="page-item">
                            <a class="page-link" href="{{ $paginator->appends(request()->query())->url($i) }}">{{ $i }}</a>
                        </li>
                        @endif
                        @endfor

                        {{-- Last page --}}
                        @if ($end < $total)
                            @if ($end < $total - 1)
                            <li class="page-item disabled">
                            <span class="page-link">...</span>
                            </li>
                            @endif
                            <li class="page-item">
                                <a class="page-link" href="{{ $paginator->appends(request()->query())->url($total) }}">{{ $total }}</a>
                            </li>
                            @endif

                            {{-- Next Page Link --}}
                            @if ($paginator->hasMorePages())
                            <li class="page-item">
                                <a class="page-link" href="{{ $paginator->appends(request()->query())->nextPageUrl() }}">
                                    Next <i class="ti ti-chevron-right"></i>
                                </a>
                            </li>
                            @else
                            <li class="page-item disabled">
                                <span class="page-link">
                                    Next <i class="ti ti-chevron-right"></i>
                                </span>
                            </li>
                            @endif
                </ul>
            </nav>
        </div>
        @endif
    </div>
</div>

<!-- Pagination JavaScript -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const perPageSelect = document.getElementById('perPageSelect');

        if (perPageSelect) {
            perPageSelect.addEventListener('change', function() {
                const selectedValue = this.value;
                const url = new URL(window.location);

                if (selectedValue === 'all') {
                    // For 'all', we'll set a very high number or handle it specially
                    url.searchParams.set('per_page', 'all');
                } else {
                    url.searchParams.set('per_page', selectedValue);
                }

                // Reset to page 1 when changing per page
                url.searchParams.delete('page');

                // Redirect to new URL
                window.location.href = url.toString();
            });
        }
    });
</script>

<!-- Pagination Styles -->
<style>
    .global-pagination-container {
        margin-top: 20px;
        padding: 15px 0;
        border-top: 1px solid #e9ecef;
    }

    .pagination-single-row {
        background: #f8f9fa;
        padding: 10px 15px;
        border-radius: 8px;
        margin-bottom: 15px;
        flex-wrap: nowrap;
    }

    .per-page-dropdown {
        display: flex;
        align-items: center;
        white-space: nowrap;
    }

    .per-page-dropdown .form-select {
        min-width: 80px;
    }

    .pagination-links .pagination {
        margin-bottom: 0;
    }

    .records-info {
        white-space: nowrap;
        flex-shrink: 0;
    }

    .pagination-links .page-link {
        color: #6c757d;
        border: 1px solid #dee2e6;
        padding: 8px 12px;
        margin: 0 2px;
        border-radius: 6px;
        transition: all 0.2s ease;
    }

    .pagination-links .page-link:hover {
        color: #495057;
        background-color: #e9ecef;
        border-color: #adb5bd;
    }

    .pagination-links .page-item.active .page-link {
        background-color: #0d6efd;
        border-color: #0d6efd;
        color: white;
    }

    .pagination-links .page-item.disabled .page-link {
        color: #6c757d;
        background-color: #fff;
        border-color: #dee2e6;
        cursor: not-allowed;
    }

    .records-info {
        font-size: 14px;
    }

    .page-info {
        font-size: 14px;
    }

    @media (max-width: 768px) {
        .pagination-single-row {
            flex-direction: column;
            gap: 10px;
            align-items: center !important;
        }

        .pagination-links {
            order: 1;
        }

        .records-info {
            order: 2;
            text-align: center;
        }

        .per-page-dropdown {
            order: 3;
            justify-content: center;
        }

        .pagination-links .pagination {
            justify-content: center;
        }
    }

    @media (max-width: 576px) {
        .pagination-single-row {
            padding: 8px 10px;
        }

        .pagination-links .page-link {
            padding: 6px 8px;
            font-size: 14px;
        }

        .per-page-dropdown .form-select {
            min-width: 70px;
            font-size: 14px;
        }
    }
</style>
@endif