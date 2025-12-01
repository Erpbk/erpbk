<?php if($paginator->hasPages()): ?>
<div class="dataTables_info" id="dataTableBuilder_info" role="status" aria-live="polite" style="margin-top: 15px;">
    Showing <?php echo e($paginator->count()); ?> of <?php echo e($paginator->total()); ?> entries
</div>

<div class="dataTables_paginate paging_simple_numbers mt-2" id="dataTableBuilder_paginate">
    <ul class="pagination justify-content-end">

        
        <?php if($paginator->onFirstPage()): ?>
        <li class="paginate_button page-item previous disabled">
            <a href="javascript:void(0)" class="page-link">Previous</a>
        </li>
        <?php else: ?>
        <li class="paginate_button page-item previous">
            <a href="<?php echo e($paginator->previousPageUrl()); ?>" class="page-link">Previous</a>
        </li>
        <?php endif; ?>

        
        <?php
        $total = $paginator->lastPage();
        $current = $paginator->currentPage();
        $start = max(1, $current - 2);
        $end = min($total, $current + 4);
        ?>

        
        <?php if($start > 1): ?>
        <li class="paginate_button page-item">
            <a href="<?php echo e($paginator->url(1)); ?>" class="page-link">1</a>
        </li>
        <?php if($start > 2): ?>
        <li class="paginate_button page-item disabled"><a class="page-link">...</a></li>
        <?php endif; ?>
        <?php endif; ?>

        
        <?php for($i = $start; $i <= $end; $i++): ?>
            <?php if($i==$current): ?>
            <li class="paginate_button page-item active">
            <a href="<?php echo e($paginator->appends(request()->query())->url($i)); ?>" class="page-link"><?php echo e($i); ?></a>

            </li>
            <?php else: ?>
            <li class="paginate_button page-item">
                <a href="<?php echo e($paginator->appends(request()->query())->url($i)); ?>" class="page-link"><?php echo e($i); ?></a>

            </li>
            <?php endif; ?>
            <?php endfor; ?>

            
            <?php if($end < $total): ?>
                <?php if($end < $total - 1): ?>
                <li class="paginate_button page-item disabled"><a class="page-link">...</a></li>
                <?php endif; ?>
                <li class="paginate_button page-item">
                    <a href="<?php echo e($paginator->url($total)); ?>" class="page-link"><?php echo e($total); ?></a>
                </li>
                <?php endif; ?>

                
                <?php if($paginator->hasMorePages()): ?>
                <li class="paginate_button page-item next">
                    <a href="<?php echo e($paginator->nextPageUrl()); ?>" class="page-link">Next</a>
                </li>
                <?php else: ?>
                <li class="paginate_button page-item next disabled">
                    <a href="javascript:void(0)" class="page-link">Next</a>
                </li>
                <?php endif; ?>

    </ul>
</div>
<?php endif; ?><?php /**PATH /var/www/laravel/resources/views/pagination.blade.php ENDPATH**/ ?>