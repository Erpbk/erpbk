<?php

namespace App\Services;

use App\Models\DeleteRequest;
use App\Models\User;
use App\Models\UserNotification;
use App\Support\CompanyContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class DeleteRequestService
{
    protected static bool $bypassing = false;

    protected static bool $resolvingPendingIds = false;

    protected static ?DeleteRequest $activeRootRequest = null;

    /** @var array<string, array<int, int>> */
    protected static array $pendingIdsCache = [];

    protected static ?bool $enabledCache = null;

    public static function bypass(bool $value = true): void
    {
        static::$bypassing = $value;
    }

    public static function isBypassing(): bool
    {
        return static::$bypassing;
    }

    public static function isResolvingPendingIds(): bool
    {
        return static::$resolvingPendingIds;
    }

    public static function enabled(): bool
    {
        if (static::$enabledCache !== null) {
            return static::$enabledCache;
        }

        try {
            static::$enabledCache = (bool) config('delete_approval.enabled', true)
                && Schema::hasTable('delete_requests');
        } catch (\Throwable $e) {
            static::$enabledCache = false;
        }

        return static::$enabledCache;
    }

    /**
     * Whether this delete should skip the approval queue (approve/reject execution only).
     * Admins do NOT bypass by default — every user delete creates a request.
     */
    public static function shouldBypassApproval(?User $user = null): bool
    {
        if (static::$bypassing) {
            return true;
        }

        // Optional escape hatch only if explicitly enabled in config/env.
        if (config('delete_approval.admins_bypass', false)) {
            $user = $user ?? Auth::user();

            return $user instanceof User && $user->isAdmin();
        }

        return false;
    }

    public static function usesSoftDeletes(Model $model): bool
    {
        return in_array(SoftDeletes::class, class_uses_recursive($model), true);
    }

    /**
     * @return array{key: string, name: string, display_columns: array<int, string>, show_route: ?string}
     */
    public static function resolveModule(Model $model): array
    {
        $class = get_class($model);
        foreach (config('delete_approval.modules', []) as $key => $meta) {
            if (($meta['model'] ?? null) === $class) {
                return [
                    'key' => (string) $key,
                    'name' => (string) ($meta['name'] ?? Str::headline($key)),
                    'display_columns' => array_values($meta['display_columns'] ?? []),
                    'show_route' => $meta['show_route'] ?? null,
                ];
            }
        }

        $base = class_basename($model);

        return [
            'key' => Str::snake($base),
            'name' => Str::headline($base),
            'display_columns' => [],
            'show_route' => null,
        ];
    }

    public static function buildLabel(Model $model, array $displayColumns = []): string
    {
        foreach ($displayColumns as $column) {
            $value = data_get($model, $column);
            if ($value !== null && $value !== '') {
                return (string) $value;
            }
        }

        foreach (['name', 'title', 'plate', 'code', 'account_code', 'rider_id', 'email'] as $fallback) {
            $value = data_get($model, $fallback);
            if ($value !== null && $value !== '') {
                return (string) $value;
            }
        }

        return class_basename($model) . ' #' . $model->getKey();
    }

    public static function buildSnapshot(Model $model, array $displayColumns = []): array
    {
        $snapshot = [
            'id' => $model->getKey(),
            'class' => get_class($model),
        ];

        $columns = $displayColumns !== []
            ? $displayColumns
            : array_slice(array_keys($model->getAttributes()), 0, 12);

        foreach ($columns as $column) {
            if (array_key_exists($column, $model->getAttributes())) {
                $snapshot[$column] = $model->getAttribute($column);
            }
        }

        return $snapshot;
    }

    public static function clearPendingIdsCache(?string $modelClass = null): void
    {
        if ($modelClass === null) {
            static::$pendingIdsCache = [];

            return;
        }

        unset(static::$pendingIdsCache[$modelClass]);
    }

    public static function hasPending(Model $model): bool
    {
        if (! static::enabled() || ! $model->exists) {
            return false;
        }

        return in_array((int) $model->getKey(), static::pendingIdsFor(get_class($model)), true);
    }

    public static function pendingRequestIdFor(Model $model): ?int
    {
        if (! static::enabled() || ! $model->exists) {
            return null;
        }

        static::$resolvingPendingIds = true;
        try {
            $id = DeleteRequest::query()
                ->pending()
                ->where('deletable_type', get_class($model))
                ->where('deletable_id', $model->getKey())
                ->value('id');

            return $id !== null ? (int) $id : null;
        } finally {
            static::$resolvingPendingIds = false;
        }
    }

    /**
     * Module show URL for admin review (same screen as the original module).
     */
    public static function moduleShowUrl(DeleteRequest $deleteRequest): ?string
    {
        $routeName = null;
        foreach (config('delete_approval.modules', []) as $key => $meta) {
            if ($key === $deleteRequest->module_key || ($meta['model'] ?? null) === $deleteRequest->deletable_type) {
                $routeName = $meta['show_route'] ?? null;
                break;
            }
        }

        if (! $routeName || ! \Illuminate\Support\Facades\Route::has($routeName)) {
            return null;
        }

        $companySlug = request()->route('company_slug') ?? null;
        if (! $companySlug && request()->hasSession()) {
            $companySlug = session('company_slug');
        }
        if (! $companySlug) {
            try {
                $companySlug = \App\Support\CompanyRouteContext::slug();
            } catch (\Throwable $e) {
                $companySlug = null;
            }
        }

        $paramName = 'id';
        try {
            $route = app('router')->getRoutes()->getByName($routeName);
            if ($route) {
                foreach ($route->parameterNames() as $name) {
                    if ($name !== 'company_slug') {
                        $paramName = $name;
                        break;
                    }
                }
            }
        } catch (\Throwable $e) {
            // keep default id
        }

        $params = [$paramName => $deleteRequest->deletable_id];
        if ($companySlug) {
            $params['company_slug'] = $companySlug;
        }

        try {
            $url = route($routeName, $params);
        } catch (\Throwable $e) {
            Log::warning('Could not build module show URL for delete request', [
                'delete_request_id' => $deleteRequest->id,
                'route' => $routeName,
                'params' => $params,
                'error' => $e->getMessage(),
            ]);

            return null;
        }

        $separator = str_contains($url, '?') ? '&' : '?';

        return $url . $separator . 'delete_request=' . $deleteRequest->id;
    }

    /**
     * @return array<int, int>
     */
    public static function pendingIdsFor(string $modelClass): array
    {
        if (! static::enabled()) {
            return [];
        }

        if (array_key_exists($modelClass, static::$pendingIdsCache)) {
            return static::$pendingIdsCache[$modelClass];
        }

        static::$resolvingPendingIds = true;
        try {
            $ids = DeleteRequest::query()
                ->pending()
                ->where('deletable_type', $modelClass)
                ->pluck('deletable_id')
                ->map(fn ($id) => (int) $id)
                ->all();

            // Include same-type IDs queued as cascades (e.g. sibling vouchers sharing a trans_code).
            $cascadeRows = DeleteRequest::query()
                ->pending()
                ->whereNotNull('cascaded_records')
                ->get(['cascaded_records']);

            foreach ($cascadeRows as $row) {
                foreach ($row->cascaded_records ?? [] as $cascaded) {
                    if (($cascaded['type'] ?? null) === $modelClass && isset($cascaded['id'])) {
                        $ids[] = (int) $cascaded['id'];
                    }
                }
            }

            static::$pendingIdsCache[$modelClass] = array_values(array_unique($ids));
        } finally {
            static::$resolvingPendingIds = false;
        }

        return static::$pendingIdsCache[$modelClass];
    }

    /**
     * Hook from Model::deleting.
     * Creates a pending delete request and CANCELS the actual delete until approved.
     *
     * @return bool|null false = abort delete; null = allow delete
     */
    public static function handleDeleting(Model $model): ?bool
    {
        if (! static::enabled() || ! static::usesSoftDeletes($model)) {
            return null;
        }

        if (static::shouldBypassApproval()) {
            return null;
        }

        // Permanent force-deletes (Recycle Bin) skip the queue.
        if (method_exists($model, 'isForceDeleting') && $model->isForceDeleting()) {
            return null;
        }

        // Controller cascade after a queued parent: record child, do not delete yet.
        if (static::$activeRootRequest !== null) {
            if (! static::$activeRootRequest->isPending()) {
                static::$activeRootRequest = null;
            } elseif (
                static::$activeRootRequest->deletable_type === get_class($model)
                && (int) static::$activeRootRequest->deletable_id === (int) $model->getKey()
            ) {
                return false;
            } else {
                static::$activeRootRequest->appendCascadedRecord(
                    get_class($model),
                    $model->getKey(),
                    static::buildLabel($model, static::resolveModule($model)['display_columns'])
                );

                return false;
            }
        }

        // Already pending — block any further delete attempts.
        if (static::hasPending($model)) {
            if (app()->bound('request')) {
                request()->attributes->set('delete_approval_created', true);
                request()->attributes->set(
                    'delete_approval_request',
                    static::lastCreatedFor($model)
                );
            }

            return false;
        }

        $module = static::resolveModule($model);
        $reasonKey = (string) config('delete_approval.reason_input', 'delete_reason');
        $reason = request()->input($reasonKey) ?? request()->input('reason');

        $deleteRequest = DeleteRequest::create([
            'company_id' => $model->getAttribute('company_id') ?? CompanyContext::id(),
            'module_key' => $module['key'],
            'module_name' => $module['name'],
            'deletable_type' => get_class($model),
            'deletable_id' => $model->getKey(),
            'record_label' => static::buildLabel($model, $module['display_columns']),
            'record_snapshot' => static::buildSnapshot($model, $module['display_columns']),
            'cascaded_records' => [],
            'requested_by' => Auth::id(),
            'reason' => $reason !== null && $reason !== '' ? (string) $reason : null,
            'status' => DeleteRequest::STATUS_PENDING,
        ]);

        static::clearPendingIdsCache(get_class($model));
        static::$activeRootRequest = $deleteRequest;

        if (app()->bound('request')) {
            request()->attributes->set('delete_approval_created', true);
            request()->attributes->set('delete_approval_request', $deleteRequest);
        }

        ActivityLogger::custom('delete_requested', $module['key'], $model, [
            'delete_request_id' => $deleteRequest->id,
            'reason' => $deleteRequest->reason,
            'label' => $deleteRequest->record_label,
        ]);

        try {
            static::notifyAdministrators($deleteRequest);
        } catch (\Throwable $e) {
            Log::warning('Failed to notify admins of delete request', [
                'delete_request_id' => $deleteRequest->id,
                'error' => $e->getMessage(),
            ]);
        }

        if (app()->bound('request')) {
            app()->terminating(function () {
                static::$activeRootRequest = null;
            });
        }

        // Abort soft/hard delete — record stays until admin approves.
        return false;
    }

    public static function notifyAdministrators(DeleteRequest $deleteRequest): void
    {
        if (! Schema::hasTable('user_notifications')) {
            return;
        }

        $admins = User::role(['Administrator', 'Super Admin'])->get();
        if ($admins->isEmpty()) {
            return;
        }

        $requesterName = $deleteRequest->requester?->name ?? 'A user';
        $title = 'Delete request pending approval';
        $body = sprintf(
            '%s requested deletion of %s (%s #%s).',
            $requesterName,
            $deleteRequest->record_label ?: 'a record',
            $deleteRequest->module_name,
            $deleteRequest->deletable_id
        );

        foreach ($admins as $admin) {
            if ((int) $admin->id === (int) $deleteRequest->requested_by) {
                continue;
            }

            UserNotification::create([
                'company_id' => $deleteRequest->company_id,
                'user_id' => $admin->id,
                'type' => 'delete_request_pending',
                'title' => $title,
                'body' => $body,
                'data' => [
                    'delete_request_id' => $deleteRequest->id,
                    'module_key' => $deleteRequest->module_key,
                    'module_name' => $deleteRequest->module_name,
                    'deletable_id' => $deleteRequest->deletable_id,
                ],
            ]);

            if (! empty($admin->email) && filter_var($admin->email, FILTER_VALIDATE_EMAIL)) {
                try {
                    Mail::raw(
                        $body . "\n\nOpen Delete Requests in the Settings Panel to approve or reject.",
                        function ($message) use ($admin, $title) {
                            $message->to($admin->email)->subject($title);
                        }
                    );
                } catch (\Throwable $mailError) {
                    Log::warning('Delete request email notification failed', [
                        'user_id' => $admin->id,
                        'error' => $mailError->getMessage(),
                    ]);
                }
            }
        }
    }

    /**
     * Approve: soft-delete only (move to Recycle Bin). Never permanently deletes.
     * Related cascaded soft-deletes stay recoverable until Recycle Bin purge.
     */
    public static function approve(DeleteRequest $deleteRequest, ?User $admin = null, ?string $remarks = null): DeleteRequest
    {
        if (! $deleteRequest->isPending()) {
            throw new \RuntimeException('Only pending delete requests can be approved.');
        }

        $admin = $admin ?? Auth::user();
        $model = static::resolveDeletable($deleteRequest);

        if (! $model) {
            throw new \RuntimeException('The requested record no longer exists and cannot be moved to the Recycle Bin.');
        }

        if (! static::usesSoftDeletes($model)) {
            throw new \RuntimeException('This record type does not support Recycle Bin soft-delete.');
        }

        $movedAt = now();
        $keepPaidExpense = static::shouldKeepPaidExpense($model);

        static::bypass(true);
        try {
            // Paid visa/license expenses stay in place; only related financials are removed.
            if (! $keepPaidExpense && method_exists($model, 'trashed') && ! $model->trashed()) {
                if (Schema::hasColumn($model->getTable(), 'deleted_by') && $admin?->id) {
                    $model->deleted_by = $admin->id;
                    $model->save();
                }
                $model->delete();
            } elseif ($keepPaidExpense && Schema::hasColumn($model->getTable(), 'deleted_by') && $model->deleted_by) {
                $model->deleted_by = null;
                $model->save();
            }

            // Soft-delete queued related records so they remain intact for restore.
            // Non-soft-deletable cascades (e.g. Payment) are finalized in afterApprovedSoftDelete.
            foreach ($deleteRequest->cascaded_records ?? [] as $cascaded) {
                $type = $cascaded['type'] ?? null;
                $id = $cascaded['id'] ?? null;
                if (! $type || ! $id || ! class_exists($type)) {
                    continue;
                }
                /** @var Model|null $related */
                $related = static::findModel($type, $id);
                if ($related && static::usesSoftDeletes($related) && method_exists($related, 'trashed') && ! $related->trashed()) {
                    if (Schema::hasColumn($related->getTable(), 'deleted_by') && $admin?->id) {
                        $related->deleted_by = $admin->id;
                        $related->save();
                    }
                    $related->delete();
                }
            }

            // Module-specific post soft-delete work (ledger, cascade audit rows, etc.).
            static::afterApprovedSoftDelete($deleteRequest, $model, $admin);
        } finally {
            static::bypass(false);
        }

        $binOutcome = $keepPaidExpense
            ? DeleteRequest::BIN_PAYMENT_REVERSED
            : DeleteRequest::BIN_IN_RECYCLE_BIN;

        $deleteRequest->update([
            'status' => DeleteRequest::STATUS_APPROVED,
            'reviewed_by' => $admin?->id,
            'reviewed_at' => $movedAt,
            'admin_remarks' => $remarks,
            'moved_to_bin_at' => $keepPaidExpense ? null : $movedAt,
            'bin_outcome' => $binOutcome,
            'restored_by' => null,
            'restored_at' => null,
            'permanently_deleted_by' => null,
            'permanently_deleted_at' => null,
        ]);

        static::clearPendingIdsCache($deleteRequest->deletable_type);
        static::$activeRootRequest = null;

        $freshModel = static::resolveDeletable($deleteRequest) ?? $model;
        ActivityLogger::custom(
            $keepPaidExpense ? 'payment_reversed' : 'moved_to_recycle_bin',
            $deleteRequest->module_key,
            $freshModel,
            [
                'delete_request_id' => $deleteRequest->id,
                'requested_by' => $deleteRequest->requested_by,
                'approved_by' => $admin?->id,
                'moved_to_bin_at' => $keepPaidExpense ? null : $movedAt->toDateTimeString(),
                'admin_remarks' => $remarks,
                'label' => $deleteRequest->record_label,
                'outcome' => $binOutcome,
            ]
        );

        return $deleteRequest->fresh(['requester', 'reviewer', 'restoredByUser', 'permanentlyDeletedByUser']);
    }

    /**
     * Mark an approved delete request as restored from the Recycle Bin.
     */
    public static function markRestoredFromBin(Model $model, ?User $actor = null): ?DeleteRequest
    {
        if (! static::enabled()) {
            return null;
        }

        $deleteRequest = static::latestApprovedInBinFor($model);
        if (! $deleteRequest) {
            return null;
        }

        $actor = $actor ?? Auth::user();
        $restoredAt = now();

        $deleteRequest->update([
            'bin_outcome' => DeleteRequest::BIN_RESTORED,
            'restored_by' => $actor?->id,
            'restored_at' => $restoredAt,
        ]);

        ActivityLogger::custom('restored_from_recycle_bin', $deleteRequest->module_key, $model, [
            'delete_request_id' => $deleteRequest->id,
            'requested_by' => $deleteRequest->requested_by,
            'approved_by' => $deleteRequest->reviewed_by,
            'restored_by' => $actor?->id,
            'restored_at' => $restoredAt->toDateTimeString(),
            'moved_to_bin_at' => optional($deleteRequest->moved_to_bin_at)->toDateTimeString(),
            'label' => $deleteRequest->record_label,
            'outcome' => DeleteRequest::BIN_RESTORED,
        ]);

        return $deleteRequest->fresh(['requester', 'reviewer', 'restoredByUser']);
    }

    /**
     * Mark an approved delete request as permanently deleted from the Recycle Bin.
     */
    public static function markPermanentlyDeletedFromBin(Model $model, ?User $actor = null): ?DeleteRequest
    {
        if (! static::enabled()) {
            return null;
        }

        $deleteRequest = static::latestApprovedInBinFor($model)
            ?? static::latestApprovedFor($model);
        if (! $deleteRequest || $deleteRequest->wasPermanentlyDeleted()) {
            return null;
        }

        $actor = $actor ?? Auth::user();
        $purgedAt = now();

        $deleteRequest->update([
            'bin_outcome' => DeleteRequest::BIN_PERMANENTLY_DELETED,
            'permanently_deleted_by' => $actor?->id,
            'permanently_deleted_at' => $purgedAt,
        ]);

        ActivityLogger::custom('permanently_deleted_from_recycle_bin', $deleteRequest->module_key, $model, [
            'delete_request_id' => $deleteRequest->id,
            'requested_by' => $deleteRequest->requested_by,
            'approved_by' => $deleteRequest->reviewed_by,
            'permanently_deleted_by' => $actor?->id,
            'permanently_deleted_at' => $purgedAt->toDateTimeString(),
            'moved_to_bin_at' => optional($deleteRequest->moved_to_bin_at)->toDateTimeString(),
            'label' => $deleteRequest->record_label,
            'outcome' => DeleteRequest::BIN_PERMANENTLY_DELETED,
        ]);

        return $deleteRequest->fresh(['requester', 'reviewer', 'permanentlyDeletedByUser']);
    }

    public static function latestApprovedInBinFor(Model $model): ?DeleteRequest
    {
        return DeleteRequest::query()
            ->where('deletable_type', get_class($model))
            ->where('deletable_id', $model->getKey())
            ->where('status', DeleteRequest::STATUS_APPROVED)
            ->where('bin_outcome', DeleteRequest::BIN_IN_RECYCLE_BIN)
            ->latest('id')
            ->first();
    }

    public static function latestApprovedFor(Model $model): ?DeleteRequest
    {
        return DeleteRequest::query()
            ->where('deletable_type', get_class($model))
            ->where('deletable_id', $model->getKey())
            ->where('status', DeleteRequest::STATUS_APPROVED)
            ->latest('id')
            ->first();
    }

    /**
     * Reject: record was never deleted — just unlock it by clearing pending status.
     */
    public static function reject(DeleteRequest $deleteRequest, ?User $admin = null, ?string $remarks = null): DeleteRequest
    {
        if (! $deleteRequest->isPending()) {
            throw new \RuntimeException('Only pending delete requests can be rejected.');
        }

        $admin = $admin ?? Auth::user();

        // If an older flow soft-deleted before this fix, restore for safety.
        static::bypass(true);
        try {
            $model = static::resolveDeletable($deleteRequest);
            if ($model && method_exists($model, 'restore') && $model->trashed()) {
                $model->restore();
            }

            if ($model && Schema::hasColumn($model->getTable(), 'deleted_by') && $model->deleted_by) {
                $model->deleted_by = null;
                $model->save();
            }

            foreach ($deleteRequest->cascaded_records ?? [] as $cascaded) {
                $type = $cascaded['type'] ?? null;
                $id = $cascaded['id'] ?? null;
                if (! $type || ! $id || ! class_exists($type)) {
                    continue;
                }
                /** @var Model|null $related */
                $related = static::findModel($type, $id);
                if ($related && method_exists($related, 'restore') && method_exists($related, 'trashed') && $related->trashed()) {
                    $related->restore();
                }
                if ($related && Schema::hasColumn($related->getTable(), 'deleted_by') && $related->deleted_by) {
                    $related->deleted_by = null;
                    $related->save();
                }
            }
        } finally {
            static::bypass(false);
        }

        $deleteRequest->update([
            'status' => DeleteRequest::STATUS_REJECTED,
            'reviewed_by' => $admin?->id,
            'reviewed_at' => now(),
            'admin_remarks' => $remarks,
            'bin_outcome' => null,
            'moved_to_bin_at' => null,
        ]);

        static::clearPendingIdsCache($deleteRequest->deletable_type);
        static::$activeRootRequest = null;

        $model = static::resolveDeletable($deleteRequest);

        ActivityLogger::custom('delete_rejected', $deleteRequest->module_key, $model, [
            'delete_request_id' => $deleteRequest->id,
            'admin_remarks' => $remarks,
            'label' => $deleteRequest->record_label,
        ]);

        return $deleteRequest->fresh(['requester', 'reviewer']);
    }

    public static function resolveDeletable(DeleteRequest $deleteRequest): ?Model
    {
        $type = $deleteRequest->deletable_type;
        if (! $type || ! class_exists($type)) {
            return null;
        }

        return static::findModel($type, $deleteRequest->deletable_id);
    }

    /**
     * Find a model by class + id, including soft-deleted rows when SoftDeletes is used.
     * Payment and other hard-delete models do not support withTrashed().
     *
     * @param  class-string<Model>  $type
     */
    public static function findModel(string $type, mixed $id): ?Model
    {
        if (! class_exists($type) || $id === null || $id === '') {
            return null;
        }

        /** @var Model $instance */
        $instance = new $type;
        if (static::usesSoftDeletes($instance)) {
            return $type::withTrashed()->find($id);
        }

        return $type::query()->find($id);
    }

    /**
     * Block updates on records that have a pending delete request.
     */
    public static function handleUpdating(Model $model): bool
    {
        if (! static::enabled() || static::$bypassing) {
            return true;
        }

        if (static::hasPending($model)) {
            return false;
        }

        return true;
    }

    public static function pendingMessage(?DeleteRequest $request = null): string
    {
        $id = $request?->id;
        $suffix = $id ? " (Request #{$id})" : '';

        return 'Delete request submitted and awaiting administrator approval.' . $suffix
            . ' The record is locked (Pending Deletion) until reviewed.';
    }

    public static function lastCreatedFor(Model $model): ?DeleteRequest
    {
        return DeleteRequest::query()
            ->where('deletable_type', get_class($model))
            ->where('deletable_id', $model->getKey())
            ->latest('id')
            ->first();
    }

    /**
     * Extra work after an approved soft-delete (still inside bypass).
     */
    protected static function afterApprovedSoftDelete(DeleteRequest $deleteRequest, Model $model, ?User $admin): void
    {
        if ($deleteRequest->module_key === 'vouchers' && $model instanceof \App\Models\Vouchers) {
            static::finalizeApprovedVoucherDeletion($deleteRequest, $model, $admin);
        }

        if ($deleteRequest->module_key === 'visa_installment_plans' && $model instanceof \App\Models\visa_installment_plan) {
            static::finalizeApprovedVisaInstallmentDeletion($deleteRequest, $model, $admin);
        }

        if ($deleteRequest->module_key === 'license_installment_plans' && $model instanceof \App\Models\license_installment_plan) {
            static::finalizeApprovedVisaInstallmentDeletion($deleteRequest, $model, $admin);
        }

        if ($deleteRequest->module_key === 'visa_expenses' && $model instanceof \App\Models\visa_expenses) {
            static::finalizeApprovedVisaExpenseDeletion($deleteRequest, $model, $admin);
        }

        if ($deleteRequest->module_key === 'license_expenses' && $model instanceof \App\Models\license_expenses) {
            static::finalizeApprovedLicenseExpenseDeletion($deleteRequest, $model, $admin);
        }

        if ($deleteRequest->module_key === 'salik' && $model instanceof \App\Models\salik) {
            static::finalizeApprovedSalikDeletion($deleteRequest, $model, $admin);
        }

        if ($deleteRequest->module_key === 'sim_invoices' && $model instanceof \App\Models\SimInvoice) {
            static::finalizeApprovedSimInvoiceDeletion($deleteRequest, $model, $admin);
        }

        if ($deleteRequest->module_key === 'supplier_invoices' && $model instanceof \App\Models\SupplierInvoices) {
            static::finalizeApprovedSupplierInvoiceDeletion($deleteRequest, $model, $admin);
        }

        if ($deleteRequest->module_key === 'suppliers' && $model instanceof \App\Models\Supplier) {
            static::finalizeApprovedSupplierDeletion($deleteRequest, $model, $admin);
        }
    }

    /**
     * After supplier-invoice approval: reassign used stock (same garage), then
     * soft-delete inventory purchases and SUP ledger rows so they stay in the bin.
     */
    protected static function finalizeApprovedSupplierInvoiceDeletion(
        DeleteRequest $deleteRequest,
        \App\Models\SupplierInvoices $invoice,
        ?User $admin
    ): void {
        $invoice->finalizeSoftDeletion($admin?->id);
    }

    /**
     * After supplier approval: finalize any cascaded purchase orders / invoices.
     */
    protected static function finalizeApprovedSupplierDeletion(
        DeleteRequest $deleteRequest,
        \App\Models\Supplier $supplier,
        ?User $admin
    ): void {
        foreach ($deleteRequest->cascaded_records ?? [] as $cascaded) {
            $type = $cascaded['type'] ?? null;
            $id = $cascaded['id'] ?? null;
            if ($type !== \App\Models\SupplierInvoices::class || ! $id) {
                continue;
            }

            $invoice = \App\Models\SupplierInvoices::withTrashed()->find($id);
            if ($invoice) {
                $invoice->finalizeSoftDeletion($admin?->id);
            }
        }
    }

    /**
     * After SIM invoice approval: soft-delete its ledger transactions so they stay
     * recoverable alongside the invoice in the Recycle Bin.
     */
    protected static function finalizeApprovedSimInvoiceDeletion(
        DeleteRequest $deleteRequest,
        \App\Models\SimInvoice $invoice,
        ?User $admin
    ): void {
        $transactions = \App\Models\Transactions::withTrashed()
            ->where('reference_type', 'SimInvoice')
            ->where('reference_id', $invoice->id)
            ->get();

        foreach ($transactions as $transaction) {
            if ($transaction->trashed()) {
                continue;
            }

            if (Schema::hasColumn($transaction->getTable(), 'deleted_by') && $admin?->id) {
                $transaction->deleted_by = $admin->id;
                $transaction->save();
            }

            $transaction->delete();
        }
    }

    /**
     * After salik soft-delete approval: rebuild the rider/company monthly invoice
     * so ledger charges exclude the trashed trip (and stay linked to an active trip).
     */
    protected static function finalizeApprovedSalikDeletion(
        DeleteRequest $deleteRequest,
        \App\Models\salik $salikRecord,
        ?User $admin
    ): void {
        if (! $salikRecord->billing_month) {
            return;
        }

        if (! $salikRecord->rider_id && ! $salikRecord->rental_company_id) {
            return;
        }

        try {
            app(\App\Http\Controllers\SalikController::class)->syncMonthlyInvoiceTransactions(
                $salikRecord->rider_id ? (int) $salikRecord->rider_id : null,
                $salikRecord->billing_month,
                $salikRecord->rental_company_id ? (int) $salikRecord->rental_company_id : null
            );
        } catch (\Throwable $e) {
            Log::error('Failed to sync salik invoice after approved soft-delete', [
                'delete_request_id' => $deleteRequest->id,
                'salik_id' => $salikRecord->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * After installment approval: soft-deletes already applied to cascaded vouchers/transactions.
     * Recalculate liability ledger for the installment billing month.
     */
    protected static function finalizeApprovedVisaInstallmentDeletion(
        DeleteRequest $deleteRequest,
        Model $installment,
        ?User $admin
    ): void {
        $billingMonth = $installment->billing_month;
        $billingMonthForLedger = (strlen((string) $billingMonth) <= 7)
            ? $billingMonth . '-01'
            : $billingMonth;

        $liabilityAccount = null;
        try {
            $rider = \App\Models\Riders::find($installment->rider_id)
                ?? \App\Models\ExpenseAccount::find($installment->rider_id)?->rider;
            if ($rider) {
                $liabilityAccount = \App\Models\Accounts::where('ref_id', $rider->id)
                    ->where('account_type', 'Liability')
                    ->orderBy('id')
                    ->first();
            }
        } catch (\Throwable $e) {
            Log::warning('Could not resolve liability account on installment approve', [
                'delete_request_id' => $deleteRequest->id,
                'installment_id' => $installment->id,
                'error' => $e->getMessage(),
            ]);
        }

        if (!$liabilityAccount) {
            return;
        }

        static::recalculateLedgerAfterVoucherDeletion((int) $liabilityAccount->id, $billingMonthForLedger);
    }

    /**
     * After visa expense approval: reverse payment in place, or fully delete unpaid rows.
     */
    protected static function finalizeApprovedVisaExpenseDeletion(
        DeleteRequest $deleteRequest,
        \App\Models\visa_expenses $expense,
        ?User $admin
    ): void {
        static::finalizeApprovedExpenseEntry($expense, 'LV');
    }

    /**
     * After license expense approval: reverse payment in place, or fully delete unpaid rows.
     */
    protected static function finalizeApprovedLicenseExpenseDeletion(
        DeleteRequest $deleteRequest,
        \App\Models\license_expenses $expense,
        ?User $admin
    ): void {
        static::finalizeApprovedExpenseEntry($expense, 'LE');
    }

    protected static function finalizeApprovedExpenseEntry(Model $expense, string $referenceType): void
    {
        if (static::isPaidExpense($expense)) {
            static::unpayExpenseInPlace($expense, $referenceType);

            return;
        }

        static::recalculateExpenseFinancialLedgers($expense, $referenceType);
        static::deleteOrphanExpenseAccount($expense);
    }

    public static function isPaidExpense(Model $expense): bool
    {
        return strtolower((string) ($expense->payment_status ?? '')) === 'paid';
    }

    public static function shouldKeepPaidExpense(Model $model): bool
    {
        return ($model instanceof \App\Models\visa_expenses || $model instanceof \App\Models\license_expenses)
            && static::isPaidExpense($model);
    }

    /**
     * Keep the expense row, mark it unpaid, and rebuild ledgers after financials were removed.
     */
    public static function unpayExpenseInPlace(Model $expense, string $referenceType): void
    {
        $expense->payment_status = 'unpaid';
        if (Schema::hasColumn($expense->getTable(), 'deleted_by') && $expense->deleted_by) {
            $expense->deleted_by = null;
        }
        $expense->save();

        static::recalculateExpenseFinancialLedgers($expense, $referenceType);
    }

    /**
     * Rebuild ledgers for every account touched by this expense's (trashed) payment rows.
     */
    public static function recalculateExpenseFinancialLedgers(Model $expense, string $referenceType): void
    {
        $accountIds = \App\Models\Transactions::withTrashed()
            ->where(function ($q) use ($expense, $referenceType) {
                $q->where(function ($inner) use ($expense, $referenceType) {
                    $inner->where('reference_id', $expense->id)
                        ->where('reference_type', $referenceType);
                });
                if (! empty($expense->trans_code)) {
                    $q->orWhere(function ($inner) use ($expense, $referenceType) {
                        $inner->where('trans_code', $expense->trans_code)
                            ->where('reference_type', $referenceType);
                    });
                }
            })
            ->pluck('account_id')
            ->filter()
            ->unique()
            ->values();

        foreach ($accountIds as $accountId) {
            if ($expense->billing_month) {
                static::recalculateLedgerAfterVoucherDeletion((int) $accountId, $expense->billing_month);
            }
        }

        static::recalculateExpenseRiderLedger($expense);
    }

    /**
     * Remove the parent expense account when no live expense rows remain.
     */
    public static function deleteOrphanExpenseAccount(Model $expense): void
    {
        $accountId = $expense->getAttribute('expense_account_id');
        if (! $accountId) {
            return;
        }

        $remaining = get_class($expense)::query()
            ->where('expense_account_id', $accountId)
            ->exists();

        if (! $remaining) {
            \App\Models\ExpenseAccount::query()->where('id', $accountId)->delete();
        }
    }

    /**
     * Rebuild the rider ledger for a visa/license expense billing month.
     */
    public static function recalculateExpenseRiderLedger($expense): void
    {
        if (empty($expense->billing_month) || empty($expense->rider_id)) {
            return;
        }

        $riderAccountId = \App\Support\CompanyQuery::table('accounts')
            ->where('ref_id', $expense->rider_id)
            ->value('id');

        if ($riderAccountId) {
            static::recalculateLedgerAfterVoucherDeletion((int) $riderAccountId, $expense->billing_month);
        }
    }

    /**
     * Ensure sibling vouchers + transactions are soft-deleted, track cascades for
     * Recycle Bin restore, and recalculate account ledgers.
     */
    protected static function finalizeApprovedVoucherDeletion(
        DeleteRequest $deleteRequest,
        \App\Models\Vouchers $voucher,
        ?User $admin
    ): void {
        $transCode = $voucher->trans_code;
        $billingMonth = $voucher->billing_month;
        $voucherIdentifier = $voucher->voucher_type . '-' . str_pad((string) $voucher->id, 4, '0', STR_PAD_LEFT);

        $allVouchers = \App\Models\Vouchers::withTrashed()->where('trans_code', $transCode)->get();
        foreach ($allVouchers as $sibling) {
            if (! $sibling->trashed()) {
                if (Schema::hasColumn($sibling->getTable(), 'deleted_by') && $admin?->id) {
                    $sibling->deleted_by = $admin->id;
                    $sibling->save();
                }
                $sibling->delete();
            }
        }

        $relatedTransactions = \App\Models\Transactions::withTrashed()
            ->where('trans_code', $transCode)
            ->get();
        $affectedAccounts = $relatedTransactions->pluck('account_id')->unique();

        foreach ($relatedTransactions as $transaction) {
            try {
                \App\Models\DeletionCascade::logCascade(
                    \App\Models\Vouchers::class,
                    $voucher->id,
                    $voucherIdentifier,
                    \App\Models\Transactions::class,
                    $transaction->id,
                    "Transaction #{$transaction->id} - {$transaction->narration} (Trans Code: {$transaction->trans_code})",
                    'hasMany',
                    'transactions',
                    'soft',
                    'Cascade deletion from approved voucher delete request #' . $deleteRequest->id
                );
            } catch (\Throwable $e) {
                Log::warning('Failed to track voucher cascade on approve', [
                    'delete_request_id' => $deleteRequest->id,
                    'transaction_id' => $transaction->id,
                    'error' => $e->getMessage(),
                ]);
            }

            if (! $transaction->trashed()) {
                if (Schema::hasColumn($transaction->getTable(), 'deleted_by') && $admin?->id) {
                    $transaction->deleted_by = $admin->id;
                    $transaction->save();
                }
                $transaction->delete();
            }
        }

        foreach ($affectedAccounts as $accountId) {
            if ($accountId && $billingMonth) {
                static::recalculateLedgerAfterVoucherDeletion((int) $accountId, $billingMonth);
            }
        }

        // Payment vouchers (PV): remove the payments row + reverse invoice/cheque links.
        // Payment is not soft-deletable — it is queued as a cascade and deleted here on approve.
        $relatedPayment = \App\Models\Payment::query()
            ->where('voucher_id', $voucher->id)
            ->first();

        if (! $relatedPayment) {
            foreach ($deleteRequest->cascaded_records ?? [] as $cascaded) {
                if (($cascaded['type'] ?? null) !== \App\Models\Payment::class || empty($cascaded['id'])) {
                    continue;
                }
                $relatedPayment = \App\Models\Payment::query()->find($cascaded['id']);
                if ($relatedPayment) {
                    break;
                }
            }
        }

        if ($relatedPayment) {
            try {
                PaymentDeletionService::executeAfterVoucherApproved($relatedPayment);
            } catch (\Throwable $e) {
                Log::warning('Failed to finalize related payment on approved voucher delete', [
                    'delete_request_id' => $deleteRequest->id,
                    'voucher_id' => $voucher->id,
                    'payment_id' => $relatedPayment->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        foreach ($allVouchers as $sibling) {
            if (($sibling->voucher_type ?? '') !== 'SV') {
                continue;
            }
            try {
                SalikPaymentReversalService::unpayLinkedSaliks((int) $sibling->id);
            } catch (\Throwable $e) {
                Log::warning('Failed to unpay saliks on approved SV voucher delete', [
                    'delete_request_id' => $deleteRequest->id,
                    'voucher_id' => $sibling->id,
                    'error' => $e->getMessage(),
                ]);
                throw $e;
            }
        }
    }

    public static function recalculateLedgerAfterVoucherDeletion(int $accountId, $billingMonth): void
    {
        \App\Support\CompanyQuery::table('ledger_entries')
            ->where('account_id', $accountId)
            ->where('billing_month', $billingMonth)
            ->delete();

        $lastLedger = \App\Support\CompanyQuery::table('ledger_entries')
            ->where('account_id', $accountId)
            ->where('billing_month', '<', $billingMonth)
            ->orderBy('billing_month', 'desc')
            ->first();

        $openingBalance = $lastLedger ? $lastLedger->closing_balance : 0.00;

        $monthTransactions = \App\Models\Transactions::where('account_id', $accountId)
            ->where('billing_month', $billingMonth)
            ->get();

        $debitTotal = $monthTransactions->sum('debit');
        $creditTotal = $monthTransactions->sum('credit');
        $closingBalance = $openingBalance + $debitTotal - $creditTotal;

        if ($monthTransactions->count() > 0) {
            \App\Support\CompanyQuery::insert('ledger_entries', [
                'account_id' => $accountId,
                'billing_month' => $billingMonth,
                'opening_balance' => $openingBalance,
                'debit_balance' => $debitTotal,
                'credit_balance' => $creditTotal,
                'closing_balance' => $closingBalance,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
