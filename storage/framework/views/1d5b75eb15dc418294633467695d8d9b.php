<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag; ?>
<?php foreach($attributes->onlyProps(['paginator', 'perPageOptions' => [20, 50, 100, 'all'], 'defaultPerPage' => 50]) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $attributes = $attributes->exceptProps(['paginator', 'perPageOptions' => [20, 50, 100, 'all'], 'defaultPerPage' => 50]); ?>
<?php foreach (array_filter((['paginator', 'perPageOptions' => [20, 50, 100, 'all'], 'defaultPerPage' => 50]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $__defined_vars = get_defined_vars(); ?>
<?php foreach ($attributes as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
} ?>
<?php unset($__defined_vars); ?>

<?php
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
?>

<?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($paginator->hasPages() || $paginator->total() > 0): ?>
<div class="global-pagination-container">
    <!-- Pagination Controls in One Row -->
    <div class="pagination-single-row d-flex justify-content-between align-items-center mb-3">
        <!-- Left side: Records info -->
        <div class="records-info d-flex align-items-center gap-2">
            <span class="text-muted">
                Showing <?php echo e($paginator->count()); ?> of <?php echo e($paginator->total()); ?> entries
            </span>
            <div class="per-page-dropdown">
                <label for="perPageSelect" class="form-label me-2 mb-0">Show:</label>
                <select id="perPageSelect" class="form-select form-select-sm" style="width: auto; display: inline-block;">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $perPageOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $option): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($option === 'all' || $option === -1 || $option === '-1'): ?>
                    <option value="<?php echo e($option); ?>" <?php echo e(($displayPerPage === 'all' || $currentPerPage == -1 || $currentPerPage == '-1') ? 'selected' : ''); ?>>
                        All (<?php echo e($paginator->total()); ?>)
                    </option>
                    <?php else: ?>
                    <option value="<?php echo e($option); ?>" <?php echo e($currentPerPage == $option ? 'selected' : ''); ?>>
                        <?php echo e($option); ?>

                    </option>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </select>
            </div>
        </div>

        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($paginator->hasPages()): ?>
        <div class="pagination-links">
            <nav aria-label="Pagination Navigation">
                <ul class="pagination mb-0">
                    
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($paginator->onFirstPage()): ?>
                    <li class="page-item disabled">
                        <span class="page-link">
                            <i class="ti ti-chevron-left"></i> Previous
                        </span>
                    </li>
                    <?php else: ?>
                    <li class="page-item">
                        <a class="page-link" href="<?php echo e($paginator->appends(request()->query())->previousPageUrl()); ?>">
                            <i class="ti ti-chevron-left"></i> Previous
                        </a>
                    </li>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                    
                    <?php
                    $total = $paginator->lastPage();
                    $current = $paginator->currentPage();
                    $start = max(1, $current - 2);
                    $end = min($total, $current + 2);
                    ?>

                    
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($start > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="<?php echo e($paginator->appends(request()->query())->url(1)); ?>">1</a>
                    </li>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($start > 2): ?>
                    <li class="page-item disabled">
                        <span class="page-link">...</span>
                    </li>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                    
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php for($i = $start; $i <= $end; $i++): ?>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($i==$current): ?>
                        <li class="page-item active">
                        <span class="page-link"><?php echo e($i); ?></span>
                        </li>
                        <?php else: ?>
                        <li class="page-item">
                            <a class="page-link" href="<?php echo e($paginator->appends(request()->query())->url($i)); ?>"><?php echo e($i); ?></a>
                        </li>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        <?php endfor; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                        
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($end < $total): ?>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($end < $total - 1): ?>
                            <li class="page-item disabled">
                            <span class="page-link">...</span>
                            </li>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            <li class="page-item">
                                <a class="page-link" href="<?php echo e($paginator->appends(request()->query())->url($total)); ?>"><?php echo e($total); ?></a>
                            </li>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                            
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($paginator->hasMorePages()): ?>
                            <li class="page-item">
                                <a class="page-link" href="<?php echo e($paginator->appends(request()->query())->nextPageUrl()); ?>">
                                    Next <i class="ti ti-chevron-right"></i>
                                </a>
                            </li>
                            <?php else: ?>
                            <li class="page-item disabled">
                                <span class="page-link">
                                    Next <i class="ti ti-chevron-right"></i>
                                </span>
                            </li>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </ul>
            </nav>
        </div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
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
<?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?><?php /**PATH C:\xampp\htdocs\erpbk\resources\views/components/global-pagination.blade.php ENDPATH**/ ?>