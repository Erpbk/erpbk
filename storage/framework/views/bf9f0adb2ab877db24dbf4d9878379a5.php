<?php $__env->startSection('title', 'Activity Logs'); ?>
<?php $__env->startSection('content'); ?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="card-title mb-0">Activity Logs</h4>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#filterModal">
                            <i class="ti ti-filter"></i> Filters
                        </button>
                        <button type="button" class="btn btn-outline-info btn-sm" onclick="loadStatistics()">
                            <i class="ti ti-chart-bar"></i> Statistics
                        </button>
                    </div>
                </div>

                <div class="card-body">
                    <!-- Filter Modal -->
                    <div class="modal fade" id="filterModal" tabindex="-1" aria-labelledby="filterModalLabel" aria-hidden="true">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="filterModalLabel">Filter Activity Logs</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <form method="GET" action="<?php echo e(route('activity-logs.index')); ?>">
                                    <div class="modal-body">
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="user_id" class="form-label">User</label>
                                                <select class="form-select" id="user_id" name="user_id">
                                                    <option value="">All Users</option>
                                                    <?php $__currentLoopData = $users; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $user): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                    <option value="<?php echo e($user->id); ?>" <?php echo e(request('user_id') == $user->id ? 'selected' : ''); ?>>
                                                        <?php echo e($user->name); ?>

                                                    </option>
                                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                </select>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label for="module_name" class="form-label">Module</label>
                                                <select class="form-select" id="module_name" name="module_name">
                                                    <option value="">All Modules</option>
                                                    <?php $__currentLoopData = $modules; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $module): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                    <option value="<?php echo e($module); ?>" <?php echo e(request('module_name') == $module ? 'selected' : ''); ?>>
                                                        <?php echo e(ucfirst($module)); ?>

                                                    </option>
                                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                </select>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label for="action" class="form-label">Action</label>
                                                <select class="form-select" id="action" name="action">
                                                    <option value="">All Actions</option>
                                                    <?php $__currentLoopData = $actions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $action): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                    <option value="<?php echo e($action); ?>" <?php echo e(request('action') == $action ? 'selected' : ''); ?>>
                                                        <?php echo e(ucfirst(str_replace('_', ' ', $action))); ?>

                                                    </option>
                                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                </select>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label for="date_from" class="form-label">Date From</label>
                                                <input type="date" class="form-control" id="date_from" name="date_from" value="<?php echo e(request('date_from')); ?>">
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label for="date_to" class="form-label">Date To</label>
                                                <input type="date" class="form-control" id="date_to" name="date_to" value="<?php echo e(request('date_to')); ?>">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                        <a href="<?php echo e(route('activity-logs.index')); ?>" class="btn btn-outline-warning">Clear Filters</a>
                                        <button type="submit" class="btn btn-primary">Apply Filters</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Statistics Modal -->
                    <div class="modal fade" id="statisticsModal" tabindex="-1" aria-labelledby="statisticsModalLabel" aria-hidden="true">
                        <div class="modal-dialog modal-xl">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="statisticsModalLabel">Activity Log Statistics</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body" id="statisticsContent">
                                    <div class="text-center">
                                        <div class="spinner-border" role="status">
                                            <span class="visually-hidden">Loading...</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Activity Logs Table -->
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>ID</th>
                                    <th>User</th>
                                    <th>Action</th>
                                    <th>Module</th>
                                    <th>Model</th>
                                    <th>Changes</th>
                                    <th>IP Address</th>
                                    <th>Timestamp</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $__empty_1 = true; $__currentLoopData = $activityLogs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $log): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                <tr>
                                    <td><?php echo e($log->id); ?></td>
                                    <td>
                                        <?php if($log->user): ?>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar avatar-sm me-2">
                                                <span class="avatar-initial rounded-circle bg-primary"><?php echo e(substr($log->user->name, 0, 1)); ?></span>
                                            </div>
                                            <div>
                                                <h6 class="mb-0"><?php echo e($log->user->name); ?></h6>
                                                <small class="text-muted"><?php echo e($log->user->email); ?></small>
                                            </div>
                                        </div>
                                        <?php else: ?>
                                        <span class="text-muted">System</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php echo e($log->action == 'created' ? 'success' : ($log->action == 'updated' ? 'warning' : ($log->action == 'deleted' ? 'danger' : 'info'))); ?>">
                                            <?php echo e(ucfirst(str_replace('_', ' ', $log->action))); ?>

                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary"><?php echo e(ucfirst($log->module_name)); ?></span>
                                    </td>
                                    <td>
                                        <?php if($log->model_type && $log->model_id): ?>
                                        <small class="text-muted"><?php echo e(class_basename($log->model_type)); ?> #<?php echo e($log->model_id); ?></small>
                                        <?php else: ?>
                                        <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if($log->changes): ?>
                                        <button class="btn btn-sm btn-outline-info" onclick="showChanges(<?php echo e($log->id); ?>)">
                                            <i class="ti ti-eye"></i> View
                                        </button>
                                        <?php else: ?>
                                        <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <small class="text-muted"><?php echo e($log->ip_address ?? '-'); ?></small>
                                    </td>
                                    <td>
                                        <small class="text-muted"><?php echo e($log->created_at->format('M d, Y H:i:s')); ?></small>
                                    </td>
                                    <td>
                                        <a href="<?php echo e(route('activity-logs.show', $log)); ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="ti ti-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                <tr>
                                    <td colspan="9" class="text-center py-4">
                                        <div class="text-muted">
                                            <i class="ti ti-file-text ti-lg mb-2"></i>
                                            <p>No activity logs found</p>
                                        </div>
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <?php if($activityLogs->hasPages()): ?>
                    <div class="d-flex justify-content-center mt-4">
                        <?php echo e($activityLogs->appends(request()->query())->links()); ?>

                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Changes Modal -->
<div class="modal fade" id="changesModal" tabindex="-1" aria-labelledby="changesModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="changesModalLabel">Changes Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="changesContent">
                <!-- Changes content will be loaded here -->
            </div>
        </div>
    </div>
</div>

<?php $__env->stopSection(); ?>

<?php $__env->startPush('page-scripts'); ?>
<script>
    function showChanges(logId) {
        // This would typically make an AJAX call to get the changes
        // For now, we'll show a placeholder
        document.getElementById('changesContent').innerHTML = `
        <div class="alert alert-info">
            <p>Changes details for log ID: ${logId}</p>
            <p><em>Note: This would typically show the old and new values for updated records.</em></p>
        </div>
    `;
        new bootstrap.Modal(document.getElementById('changesModal')).show();
    }

    function loadStatistics() {
        new bootstrap.Modal(document.getElementById('statisticsModal')).show();

        // Load statistics via AJAX
        fetch('<?php echo e(route("activity-logs.statistics")); ?>')
            .then(response => response.json())
            .then(data => {
                document.getElementById('statisticsContent').innerHTML = `
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <h3 class="text-primary">${data.total_activities}</h3>
                                <p class="mb-0">Total Activities</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-9 mb-3">
                        <div class="card">
                            <div class="card-header">
                                <h6>Activities by Action</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    ${data.activities_by_action.map(item => `
                                        <div class="col-md-4 mb-2">
                                            <span class="badge bg-primary me-2">${item.action}</span>
                                            <span class="text-muted">${item.count}</span>
                                        </div>
                                    `).join('')}
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="card">
                            <div class="card-header">
                                <h6>Activities by Module</h6>
                            </div>
                            <div class="card-body">
                                ${data.activities_by_module.map(item => `
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>${item.module_name}</span>
                                        <span class="badge bg-secondary">${item.count}</span>
                                    </div>
                                `).join('')}
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="card">
                            <div class="card-header">
                                <h6>Top Users</h6>
                            </div>
                            <div class="card-body">
                                ${data.activities_by_user.map(item => `
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>${item.user ? item.user.name : 'System'}</span>
                                        <span class="badge bg-info">${item.count}</span>
                                    </div>
                                `).join('')}
                            </div>
                        </div>
                    </div>
                </div>
            `;
            })
            .catch(error => {
                document.getElementById('statisticsContent').innerHTML = `
                <div class="alert alert-danger">
                    <p>Error loading statistics: ${error.message}</p>
                </div>
            `;
            });
    }
</script>
<?php $__env->stopPush(); ?>
<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH D:\xammp1\htdocs\erpbk\resources\views/activity_logs/index.blade.php ENDPATH**/ ?>