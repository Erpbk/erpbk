<?php

namespace App\Services;

use App\Models\DeleteRequest;
use App\Models\Receipt;
use App\Models\Transactions;
use App\Models\User;
use App\Models\Vouchers;
use App\Models\license_installment_plan;
use App\Models\visa_installment_plan;
use App\Support\CompanyContext;
use App\Support\ResourceLinks;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class DeleteRequestService
{
    protected static bool $bypassing = false;

    protected static bool $resolvingPendingIds = false;

    protected static ?DeleteRequest $activeRootRequest = null;

    /** @var array<string, array<int, int>> */
    protected static array $pendingIdsCache = [];

    /**
     * Request-scoped reverse index: "ModelClass|id" => delete_request_id
     * Built once with pendingIdsCache from the same pending-rows scan.
     *
     * @var array<string, int>|null
     */
    protected static ?array $pendingRequestIdByKey = null;

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

    /**
     * Human-facing resource id for notifications (PV-0001, invoice numbers, fine-…, etc.).
     */
    public static function resourceIdentifier(DeleteRequest $deleteRequest, ?Model $model = null): string
    {
        $model = $model ?? static::resolveDeletable($deleteRequest);
        $snapshot = is_array($deleteRequest->record_snapshot) ? $deleteRequest->record_snapshot : [];
        $id = (string) $deleteRequest->deletable_id;

        $attr = function (string ...$keys) use ($model, $snapshot): ?string {
            foreach ($keys as $key) {
                $value = $model !== null ? data_get($model, $key) : null;
                if ($value === null || $value === '') {
                    $value = $snapshot[$key] ?? null;
                }
                if ($value !== null && $value !== '') {
                    return (string) $value;
                }
            }

            return null;
        };

        $padded = static fn (?string $prefix, string $number, int $width = 4): string => ($prefix ?: 'V')
            . '-'
            . str_pad($number, $width, '0', STR_PAD_LEFT);

        $identifier = match ($deleteRequest->module_key) {
            'vouchers' => $padded(
                $attr('voucher_type') ?: 'V',
                $attr('id') ?: $id
            ),
            'rider_invoices' => $attr('invoice_number') ?: $padded('RINV', $id),
            'leasing_company_invoices' => $attr('invoice_number', 'leasing_company_invoice_number') ?: $padded('INV', $id),
            'sim_invoices' => $attr('invoice_number') ?: $padded('SIMI', $id),
            'supplier_invoices' => $attr('invoice_number', 'inv_id') ?: $padded('SUP', $id),
            'rta_fines' => 'fine-' . ($attr('ticket_no') ?: $id),
            'salik' => 'salik-' . ($attr('ticket_no') ?: $id),
            'cheques' => $attr('cheque_number') ?: ('cheque-' . $id),
            'bikes' => $attr('plate') ?: ('bike-' . $id),
            'riders' => $attr('rider_id') ?: ('rider-' . $id),
            'employees' => $attr('employee_id') ?: ('employee-' . $id),
            'loans' => $attr('loan_number') ?: ('loan-' . $id),
            'fixed_assets' => $attr('asset_code') ?: ('asset-' . $id),
            'accounts' => $attr('account_code') ?: ('account-' . $id),
            'fuel_cards' => $attr('card_number') ?: ('fuel-card-' . $id),
            'fuel_data' => $attr('trans_no') ?: ('fuel-' . $id),
            'sims' => $attr('number') ?: ('sim-' . $id),
            'banks' => $attr('name', 'account_no') ?: ('bank-' . $id),
            'customers',
            'vendors',
            'suppliers',
            'leasing_companies',
            'garages',
            'recruiters',
            'sim_companies',
            'bike_rent_companies',
            'fuel_companies',
            'items' => $attr('name') ?: ($deleteRequest->module_key . '-' . $id),
            'bike_maintenance' => 'maintenance-' . $id,
            'visa_expenses' => $attr('reference_number') ?: ('visa-exp-' . $id),
            'license_expenses' => $attr('reference_number') ?: ('license-exp-' . $id),
            'visa_installment_plans' => $attr('reference_number') ?: ('visa-plan-' . $id),
            'license_installment_plans' => $attr('reference_number') ?: ('license-plan-' . $id),
            'rider_inventory_assignments' => 'assignment-' . $id,
            default => null,
        };

        if ($identifier !== null && $identifier !== '') {
            return $identifier;
        }

        $label = trim((string) ($deleteRequest->record_label ?? ''));
        if ($label !== '') {
            return $label;
        }

        return ($deleteRequest->module_name ?: 'Record') . ' #' . $id;
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
        // Reverse map always depends on the full pending set — rebuild next access.
        static::$pendingRequestIdByKey = null;

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

        static::ensurePendingIndexBuilt();
        $key = static::pendingLookupKey(get_class($model), (int) $model->getKey());

        return static::$pendingRequestIdByKey[$key] ?? null;
    }

    protected static function pendingLookupKey(string $modelClass, int $id): string
    {
        return $modelClass . '|' . $id;
    }

    /**
     * Build pendingIdsCache (all classes) + pendingRequestIdByKey from one pending-rows query.
     */
    protected static function ensurePendingIndexBuilt(): void
    {
        if (static::$pendingRequestIdByKey !== null) {
            return;
        }

        if (! static::enabled()) {
            static::$pendingRequestIdByKey = [];

            return;
        }

        static::$resolvingPendingIds = true;
        try {
            $rows = DeleteRequest::query()
                ->pending()
                ->get(['id', 'deletable_type', 'deletable_id', 'cascaded_records']);

            $byClass = [];
            $byKey = [];

            foreach ($rows as $row) {
                $requestId = (int) $row->id;
                $primaryType = (string) $row->deletable_type;
                $primaryId = (int) $row->deletable_id;

                $byKey[static::pendingLookupKey($primaryType, $primaryId)] = $requestId;
                $byClass[$primaryType][] = $primaryId;

                foreach ($row->cascaded_records ?? [] as $cascaded) {
                    $type = $cascaded['type'] ?? null;
                    $id = isset($cascaded['id']) ? (int) $cascaded['id'] : 0;
                    if (! is_string($type) || $type === '' || $id <= 0) {
                        continue;
                    }
                    $key = static::pendingLookupKey($type, $id);
                    // Keep first (primary) mapping if already set.
                    if (! isset($byKey[$key])) {
                        $byKey[$key] = $requestId;
                    }
                    $byClass[$type][] = $id;
                }
            }

            foreach ($byClass as $class => $ids) {
                static::$pendingIdsCache[$class] = array_values(array_unique($ids));
            }
            static::$pendingRequestIdByKey = $byKey;
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

        static::ensurePendingIndexBuilt();

        return static::$pendingIdsCache[$modelClass] ?? [];
    }

    /**
     * Batch-resolve pending delete request ids for many models of the same class.
     *
     * @param  list<int>  $ids
     * @return array<int, int> modelId => delete_request_id
     */
    public static function pendingRequestIdsForIds(string $modelClass, array $ids): array
    {
        if (! static::enabled() || $ids === []) {
            return [];
        }

        static::ensurePendingIndexBuilt();
        $out = [];
        foreach ($ids as $id) {
            $id = (int) $id;
            if ($id <= 0) {
                continue;
            }
            $requestId = static::$pendingRequestIdByKey[static::pendingLookupKey($modelClass, $id)] ?? null;
            if ($requestId) {
                $out[$id] = $requestId;
            }
        }

        return $out;
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

        $requesterName = $deleteRequest->requester?->name ?? 'A user';
        $resourceIdentifier = static::resourceIdentifier($deleteRequest);
        $title = 'Delete request pending approval for ' . $resourceIdentifier;
        $body = sprintf(
            '%s requested deletion of %s (%s #%s).',
            $requesterName,
            $resourceIdentifier,
            $deleteRequest->module_name,
            $deleteRequest->deletable_id
        );

        $companySlug = null;
        if ($deleteRequest->company_id) {
            $companySlug = \App\Models\Company::query()->where('id', $deleteRequest->company_id)->value('slug');
        }

        $actionUrl = null;
        if ($companySlug) {
            $actionUrl = route('settings-panel.delete-requests.index', [
                'company_slug' => $companySlug,
                'search' => $deleteRequest->id,
            ], false);
        }

        try {
            app(\App\Services\Notifications\NotificationEngine::class)->dispatch([
                'type_key' => 'delete_request_pending',
                'company_id' => (int) $deleteRequest->company_id,
                'source' => $deleteRequest,
                'title' => $title,
                'body' => $body,
                'severity' => 'warning',
                'action_url' => $actionUrl,
                'data' => [
                    'delete_request_id' => $deleteRequest->id,
                    'module_key' => $deleteRequest->module_key,
                    'module_name' => $deleteRequest->module_name,
                    'deletable_id' => $deleteRequest->deletable_id,
                    'resource_identifier' => $resourceIdentifier,
                ],
                'dedupe_context' => [
                    'pending' => $deleteRequest->id,
                ],
                'exclude_user_ids' => [(int) $deleteRequest->requested_by],
            ]);
        } catch (\Throwable $e) {
            Log::warning('Delete request notification via engine failed', [
                'delete_request_id' => $deleteRequest->id,
                'error' => $e->getMessage(),
            ]);
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
        $purgeInstallment = static::shouldPurgeInstallmentOnApprove($model);
        $purgeInstallmentReceipt = static::shouldPurgeInstallmentReceiptOnApprove($model);
        $hardPurge = $purgeInstallment || $purgeInstallmentReceipt;

        static::bypass(true);
        try {
            if ($purgeInstallment) {
                static::purgeApprovedInstallmentDeletion($deleteRequest, $model, $admin);
            } elseif ($purgeInstallmentReceipt) {
                static::purgeApprovedInstallmentReceiptDeletion($deleteRequest, $model, $admin);
            } elseif ($keepPaidExpense) {
                // Paid visa/license expenses stay in place; payment vouchers/transactions are purged.
                if (Schema::hasColumn($model->getTable(), 'deleted_by') && $model->deleted_by) {
                    $model->deleted_by = null;
                    $model->save();
                }
                $referenceType = $deleteRequest->module_key === 'license_expenses' ? 'LE' : 'LV';
                [$vouchers, $transactions] = static::resolveExpensePaymentFinancials(
                    $model,
                    $referenceType,
                    $deleteRequest->cascaded_records ?? []
                );
                static::purgePaidExpensePayment($model, $referenceType, $vouchers, $transactions);
            } else {
                if (method_exists($model, 'trashed') && ! $model->trashed()) {
                    if (Schema::hasColumn($model->getTable(), 'deleted_by') && $admin?->id) {
                        $model->deleted_by = $admin->id;
                        $model->save();
                    }
                    $model->delete();
                }

                // Soft-delete queued related records so they remain intact for restore.
                // Payment invoice/cheque cleanup still runs in afterApprovedSoftDelete.
                foreach ($deleteRequest->cascaded_records ?? [] as $cascaded) {
                    $type = $cascaded['type'] ?? null;
                    $id = $cascaded['id'] ?? null;
                    if (! $type || ! $id || ! class_exists($type)) {
                        continue;
                    }
                    /** @var Model|null $related */
                    $related = static::findModel($type, $id);
                    if ($related && static::usesSoftDeletes($related) && method_exists($related, 'trashed')) {
                        if (! $related->trashed()) {
                            if (Schema::hasColumn($related->getTable(), 'deleted_by') && $admin?->id) {
                                $related->deleted_by = $admin->id;
                                $related->save();
                            }
                            $related->delete();
                        }

                        static::logApprovedCascade($deleteRequest, $related, $cascaded);
                    }
                }

                // Module-specific post soft-delete work (ledger, cascade audit rows, etc.).
                static::afterApprovedSoftDelete($deleteRequest, $model, $admin);
            }
        } finally {
            static::bypass(false);
        }

        $binOutcome = $hardPurge
            ? DeleteRequest::BIN_PERMANENTLY_DELETED
            : ($keepPaidExpense
                ? DeleteRequest::BIN_PAYMENT_REVERSED
                : DeleteRequest::BIN_IN_RECYCLE_BIN);

        $deleteRequest->update([
            'status' => DeleteRequest::STATUS_APPROVED,
            'reviewed_by' => $admin?->id,
            'reviewed_at' => $movedAt,
            'admin_remarks' => $remarks,
            'moved_to_bin_at' => ($keepPaidExpense || $hardPurge) ? null : $movedAt,
            'bin_outcome' => $binOutcome,
            'restored_by' => null,
            'restored_at' => null,
            'permanently_deleted_by' => $hardPurge ? $admin?->id : null,
            'permanently_deleted_at' => $hardPurge ? $movedAt : null,
        ]);

        static::clearPendingIdsCache($deleteRequest->deletable_type);
        static::$activeRootRequest = null;

        $freshModel = static::resolveDeletable($deleteRequest) ?? $model;
        ActivityLogger::custom(
            $hardPurge
                ? 'permanently_deleted'
                : ($keepPaidExpense ? 'payment_reversed' : 'moved_to_recycle_bin'),
            $deleteRequest->module_key,
            $freshModel,
            [
                'delete_request_id' => $deleteRequest->id,
                'requested_by' => $deleteRequest->requested_by,
                'approved_by' => $admin?->id,
                'moved_to_bin_at' => ($keepPaidExpense || $hardPurge) ? null : $movedAt->toDateTimeString(),
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
     * Write a Recycle Bin cascade row after an approved related-record soft-delete
     * so restore can bring the child back with the parent.
     */
    protected static function logApprovedCascade(DeleteRequest $deleteRequest, Model $related, array $cascaded): void
    {
        try {
            \App\Models\DeletionCascade::logCascade(
                $deleteRequest->deletable_type,
                $deleteRequest->deletable_id,
                $deleteRequest->record_label,
                get_class($related),
                $related->getKey(),
                $cascaded['label'] ?? (class_basename($related) . ' #' . $related->getKey()),
                'hasOne',
                null,
                'soft',
                'Cascade deletion from approved delete request #' . $deleteRequest->id
            );
        } catch (\Throwable $e) {
            Log::warning('Failed to track cascade on approve', [
                'delete_request_id' => $deleteRequest->id,
                'related_type' => get_class($related),
                'related_id' => $related->getKey(),
                'error' => $e->getMessage(),
            ]);
        }
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

        if ($deleteRequest->module_key === 'fuel_data' && $model instanceof \App\Models\FuelData) {
            static::finalizeApprovedFuelDataDeletion($deleteRequest, $model, $admin);
        }

        if ($deleteRequest->module_key === 'sim_invoices' && $model instanceof \App\Models\SimInvoice) {
            static::finalizeApprovedSimInvoiceDeletion($deleteRequest, $model, $admin);
        }

        if ($deleteRequest->module_key === 'supplier_invoices' && $model instanceof \App\Models\SupplierInvoices) {
            static::finalizeApprovedSupplierInvoiceDeletion($deleteRequest, $model, $admin);
        }

        if ($deleteRequest->module_key === 'bike_maintenance' && $model instanceof \App\Models\BikeMaintenance) {
            static::finalizeApprovedBikeMaintenanceDeletion($deleteRequest, $model, $admin);
        }

        if ($deleteRequest->module_key === 'suppliers' && $model instanceof \App\Models\Supplier) {
            static::finalizeApprovedSupplierDeletion($deleteRequest, $model, $admin);
        }

        if ($deleteRequest->module_key === 'vat_returns' && $model instanceof \App\Models\VatReturn) {
            static::finalizeApprovedVatReturnDeletion($deleteRequest, $model, $admin);
        }
    }

    /**
     * Soft-delete clearing ledger for an approved VAT return delete.
     */
    protected static function finalizeApprovedVatReturnDeletion(
        DeleteRequest $deleteRequest,
        \App\Models\VatReturn $vatReturn,
        ?User $admin
    ): void {
        if ($vatReturn->hasLivePayments()) {
            throw new \RuntimeException(
                'Cannot approve delete for a VAT return that still has linked payments. Delete the payments first.'
            );
        }

        $vatReturn->softDeleteLinkedClearing($admin?->id);
    }

    /**
     * After bike-maintenance approval: soft-delete line items + ledger and return stock.
     */
    protected static function finalizeApprovedBikeMaintenanceDeletion(
        DeleteRequest $deleteRequest,
        \App\Models\BikeMaintenance $maintenance,
        ?User $admin
    ): void {
        $maintenance->finalizeSoftDeletion($admin?->id);
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
        $invoice->finalizeSoftDeletion($admin?->id);
    }

    /**
     * After fuel transaction soft-delete approval: rebuild monthly fuel ledger
     * so rider charges exclude the trashed line.
     */
    protected static function finalizeApprovedFuelDataDeletion(
        DeleteRequest $deleteRequest,
        \App\Models\FuelData $fuelData,
        ?User $admin
    ): void {
        if (! $fuelData->rider_id || ! $fuelData->billing_month) {
            return;
        }

        try {
            app(\App\Services\FuelMonthlyLedgerService::class)->sync(
                (int) $fuelData->rider_id,
                $fuelData->billing_month
            );
        } catch (\Throwable $e) {
            Log::error('Failed to sync fuel ledger after approved soft-delete', [
                'delete_request_id' => $deleteRequest->id,
                'fuel_data_id' => $fuelData->id,
                'error' => $e->getMessage(),
            ]);
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
     * After installment approval (legacy soft path): rebuild liability ledger.
     * Hard purge is handled by purgeApprovedInstallmentDeletion from approve().
     */
    protected static function finalizeApprovedVisaInstallmentDeletion(
        DeleteRequest $deleteRequest,
        Model $installment,
        ?User $admin
    ): void {
        static::recalculateInstallmentLiabilityLedger($installment, $deleteRequest->id);
    }

    /**
     * Hard-delete installment + cascaded receipts/vouchers/txs on approve (no recycle bin).
     */
    protected static function purgeApprovedInstallmentDeletion(
        DeleteRequest $deleteRequest,
        Model $installment,
        ?User $admin
    ): void {
        static::purgeInstallmentLinkedReceipts($installment);

        foreach ($deleteRequest->cascaded_records ?? [] as $cascaded) {
            $type = $cascaded['type'] ?? null;
            $id = $cascaded['id'] ?? null;
            if (! $type || ! $id || ! class_exists($type)) {
                continue;
            }
            /** @var Model|null $related */
            $related = static::findModel($type, $id);
            if (! $related) {
                continue;
            }
            if (method_exists($related, 'forceDelete')) {
                $related->forceDelete();
            } else {
                $related->delete();
            }
        }

        // Also force-delete VL vouchers/txs that may not be in cascaded_records.
        $isLicense = $installment instanceof license_installment_plan;
        $voucherType = $isLicense ? license_installment_plan::VOUCHER_TYPE : 'VL';
        $referenceType = $isLicense ? license_installment_plan::REFERENCE_TYPE : 'VL';
        $voucherQuery = Vouchers::withTrashed()
            ->where('ref_id', $installment->id)
            ->where('voucher_type', $voucherType);
        if ($isLicense) {
            $voucherQuery->where('reason', license_installment_plan::VOUCHER_REASON);
        }
        foreach ($voucherQuery->get() as $voucher) {
            $voucher->forceDelete();
        }
        foreach (
            Transactions::withTrashed()
                ->where('reference_id', $installment->id)
                ->where('reference_type', $referenceType)
                ->get() as $tx
        ) {
            $tx->forceDelete();
        }

        static::recalculateInstallmentLiabilityLedger($installment, $deleteRequest->id);

        if (method_exists($installment, 'forceDelete')) {
            $installment->forceDelete();
        } else {
            $installment->delete();
        }
    }

    protected static function recalculateInstallmentLiabilityLedger(Model $installment, $deleteRequestId = null): void
    {
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
                'delete_request_id' => $deleteRequestId,
                'installment_id' => $installment->id,
                'error' => $e->getMessage(),
            ]);
        }

        if (! $liabilityAccount) {
            return;
        }

        static::recalculateLedgerAfterVoucherDeletion((int) $liabilityAccount->id, $billingMonthForLedger);
    }

    /**
     * True when the installment has at least one linked payment receipt id in partial_paid_amount.
     */
    public static function installmentHasLinkedReceipts(Model $installment): bool
    {
        $partials = is_array($installment->partial_paid_amount ?? null)
            ? $installment->partial_paid_amount
            : [];

        foreach ($partials as $receiptId => $amount) {
            if ((int) $receiptId > 0 && (float) $amount > 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Hard-delete receipts posted against an installment (RV voucher + matching GL txs).
     * Sibling installments that shared a receipt are unlinked and rebalanced first.
     */
    public static function purgeInstallmentLinkedReceipts(Model $installment): void
    {
        $partials = is_array($installment->partial_paid_amount ?? null)
            ? $installment->partial_paid_amount
            : [];

        $receiptIds = [];
        foreach ($partials as $receiptId => $amount) {
            $id = (int) $receiptId;
            if ($id > 0) {
                $receiptIds[] = $id;
            }
        }
        $receiptIds = array_values(array_unique($receiptIds));
        if ($receiptIds === []) {
            return;
        }

        $wasBypassing = static::isBypassing();
        static::bypass(true);
        try {
            foreach ($receiptIds as $receiptId) {
                $receipt = Receipt::withTrashed()->find($receiptId);
                if (!$receipt) {
                    continue;
                }

                static::unlinkReceiptFromSiblingInstallments($receipt, $installment);

                $voucher = $receipt->voucher_id
                    ? Vouchers::withTrashed()->find($receipt->voucher_id)
                    : null;
                $transCode = $voucher?->trans_code;

                if ($transCode) {
                    $txs = Transactions::withTrashed()->where('trans_code', $transCode)->get();
                    foreach ($txs as $tx) {
                        if (method_exists($tx, 'forceDelete')) {
                            $tx->forceDelete();
                        } else {
                            $tx->delete();
                        }
                    }
                } else {
                    $txs = Transactions::withTrashed()
                        ->where('reference_type', 'RV')
                        ->where('reference_id', $receipt->id)
                        ->get();
                    foreach ($txs as $tx) {
                        if (method_exists($tx, 'forceDelete')) {
                            $tx->forceDelete();
                        } else {
                            $tx->delete();
                        }
                    }
                }

                if ($voucher) {
                    if (method_exists($voucher, 'forceDelete')) {
                        $voucher->forceDelete();
                    } else {
                        $voucher->delete();
                    }
                }

                if (method_exists($receipt, 'forceDelete')) {
                    $receipt->forceDelete();
                } else {
                    $receipt->delete();
                }
            }
        } finally {
            if (!$wasBypassing) {
                static::bypass(false);
            }
        }
    }

    /**
     * True when any linked receipt allocates to more than one installment.
     */
    public static function installmentHasSharedReceipt(Model $installment): bool
    {
        $partials = is_array($installment->partial_paid_amount ?? null)
            ? $installment->partial_paid_amount
            : [];

        $receiptIds = [];
        foreach ($partials as $receiptId => $amount) {
            $id = (int) $receiptId;
            if ($id > 0) {
                $receiptIds[] = $id;
            }
        }
        $receiptIds = array_values(array_unique($receiptIds));
        if ($receiptIds === []) {
            return false;
        }

        $receipts = Receipt::query()
            ->whereIn('id', $receiptIds)
            ->get(['id', 'resource_links']);

        foreach ($receipts as $receipt) {
            $links = is_array($receipt->resource_links) ? $receipt->resource_links : null;
            if (count(ResourceLinks::invoiceIds($links)) > 1) {
                return true;
            }
        }

        return false;
    }

    /**
     * Remove a receipt allocation from all linked installments (or all except $exclude).
     * Used by receipt destroy and multi-installment receipt purge.
     */
    public static function rebalanceInstallmentsForRemovedReceipt(
        Receipt $receipt,
        ?Model $excludeInstallment = null
    ): void {
        $links = is_array($receipt->resource_links) ? $receipt->resource_links : null;
        $linkType = ResourceLinks::invoiceType($links);
        $linkedIds = ResourceLinks::invoiceIds($links);
        if ($linkedIds === []) {
            return;
        }

        $modelClass = $linkType === ResourceLinks::TYPE_LICENSE_INSTALLMENT
            ? license_installment_plan::class
            : visa_installment_plan::class;

        $classes = $linkType
            ? [$modelClass]
            : [visa_installment_plan::class, license_installment_plan::class];

        $receiptId = (int) $receipt->id;

        foreach ($classes as $class) {
            $siblings = $class::withTrashed()
                ->whereIn('id', $linkedIds)
                ->get();

            foreach ($siblings as $sibling) {
                if ($excludeInstallment
                    && (int) $sibling->id === (int) $excludeInstallment->id
                    && $excludeInstallment instanceof $class
                ) {
                    continue;
                }

                static::applyInstallmentReceiptUnlink($sibling, $receiptId);
            }
        }
    }

    /**
     * Unset one receipt from an installment and recalc paid_amount / status.
     */
    public static function applyInstallmentReceiptUnlink(Model $installment, int $receiptId): void
    {
        $class = get_class($installment);
        $partials = is_array($installment->partial_paid_amount) ? $installment->partial_paid_amount : [];
        $removed = (float) ($partials[$receiptId] ?? $partials[(string) $receiptId] ?? 0);
        if ($removed <= 0
            && ! array_key_exists($receiptId, $partials)
            && ! array_key_exists((string) $receiptId, $partials)
        ) {
            return;
        }

        unset($partials[$receiptId], $partials[(string) $receiptId]);
        $installment->partial_paid_amount = $partials;
        $installment->paid_amount = max(0.0, (float) $installment->paid_amount - max(0.0, $removed));

        if ((float) $installment->paid_amount <= 0.001) {
            $installment->paid_amount = 0;
            $installment->status = defined("$class::STATUS_PENDING")
                ? $class::STATUS_PENDING
                : 'pending';
        } elseif ((float) $installment->paid_amount + 0.001 < (float) $installment->amount) {
            $installment->status = defined("$class::STATUS_PARTIAL")
                ? $class::STATUS_PARTIAL
                : 'partial';
        } else {
            $installment->status = defined("$class::STATUS_PAID")
                ? $class::STATUS_PAID
                : 'paid';
        }
        $installment->save();
    }

    /**
     * When a multi-installment receipt is purged with one installment, rebalance the others.
     */
    protected static function unlinkReceiptFromSiblingInstallments(Receipt $receipt, Model $deletedInstallment): void
    {
        static::rebalanceInstallmentsForRemovedReceipt($receipt, $deletedInstallment);
    }

    public static function shouldPurgeInstallmentOnApprove(Model $model): bool
    {
        return $model instanceof visa_installment_plan
            || $model instanceof license_installment_plan;
    }

    /**
     * Installment-linked receipts are hard-purged on approve (no recycle-bin restore).
     */
    public static function shouldPurgeInstallmentReceiptOnApprove(Model $model): bool
    {
        if (! $model instanceof Receipt) {
            return false;
        }

        $links = is_array($model->resource_links) ? $model->resource_links : null;
        $invoiceType = ResourceLinks::invoiceType($links);

        return in_array($invoiceType, [
            ResourceLinks::TYPE_VISA_INSTALLMENT,
            ResourceLinks::TYPE_LICENSE_INSTALLMENT,
        ], true);
    }

    /**
     * On approve: unlink installments, then hard-delete receipt + RV + txs.
     */
    protected static function purgeApprovedInstallmentReceiptDeletion(
        DeleteRequest $deleteRequest,
        Model $receipt,
        ?User $admin
    ): void {
        if (! $receipt instanceof Receipt) {
            return;
        }

        static::purgeReceiptAndFinancials($receipt);

        foreach ($deleteRequest->cascaded_records ?? [] as $cascaded) {
            $type = $cascaded['type'] ?? null;
            $id = $cascaded['id'] ?? null;
            if (! $type || ! $id || ! class_exists($type)) {
                continue;
            }
            /** @var Model|null $related */
            $related = static::findModel($type, $id);
            if (! $related) {
                continue;
            }
            if (method_exists($related, 'forceDelete')) {
                $related->forceDelete();
            } else {
                $related->delete();
            }
        }
    }

    /**
     * Rebalance linked installments and hard-delete one receipt with its RV/txs.
     */
    public static function purgeReceiptAndFinancials(Receipt $receipt): void
    {
        $wasBypassing = static::isBypassing();
        static::bypass(true);
        try {
            static::rebalanceInstallmentsForRemovedReceipt($receipt);

            $voucher = $receipt->voucher_id
                ? Vouchers::withTrashed()->find($receipt->voucher_id)
                : null;
            $transCode = $voucher?->trans_code;

            if ($transCode) {
                foreach (Transactions::withTrashed()->where('trans_code', $transCode)->get() as $tx) {
                    if (method_exists($tx, 'forceDelete')) {
                        $tx->forceDelete();
                    } else {
                        $tx->delete();
                    }
                }
            } else {
                foreach (
                    Transactions::withTrashed()
                        ->where('reference_type', 'RV')
                        ->where('reference_id', $receipt->id)
                        ->get() as $tx
                ) {
                    if (method_exists($tx, 'forceDelete')) {
                        $tx->forceDelete();
                    } else {
                        $tx->delete();
                    }
                }
            }

            if ($voucher) {
                if (method_exists($voucher, 'forceDelete')) {
                    $voucher->forceDelete();
                } else {
                    $voucher->delete();
                }
            }

            $fresh = Receipt::withTrashed()->find($receipt->id) ?? $receipt;
            if (method_exists($fresh, 'forceDelete')) {
                $fresh->forceDelete();
            } else {
                $fresh->delete();
            }
        } finally {
            if (! $wasBypassing) {
                static::bypass(false);
            }
        }
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
        // Paid-entry approve path purges financials before this hook; unpaid rows land here.
        if (static::isPaidExpense($expense)) {
            [$vouchers, $transactions] = static::resolveExpensePaymentFinancials($expense, $referenceType);
            static::purgePaidExpensePayment($expense, $referenceType, $vouchers, $transactions);

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
     * Collect LV/LE vouchers + transactions for a payment reverse (live and soft-deleted).
     *
     * @param  array<int, array<string, mixed>>  $cascadedRecords
     * @return array{0: Collection<int, Model>, 1: Collection<int, Model>}
     */
    public static function resolveExpensePaymentFinancials(
        Model $expense,
        string $referenceType,
        array $cascadedRecords = []
    ): array {
        $vouchers = collect();
        $transactions = collect();

        foreach ($cascadedRecords as $cascaded) {
            $type = $cascaded['type'] ?? null;
            $id = $cascaded['id'] ?? null;
            if (! $type || ! $id || ! class_exists($type)) {
                continue;
            }
            $related = static::findModel($type, $id);
            if (! $related) {
                continue;
            }
            if ($related instanceof Vouchers) {
                $vouchers->push($related);
            } elseif ($related instanceof Transactions) {
                $transactions->push($related);
            }
        }

        $voucherQuery = Vouchers::withTrashed()
            ->where('ref_id', $expense->id)
            ->where('voucher_type', $referenceType);
        if ($referenceType === 'LE') {
            $voucherQuery->where(function ($q) {
                $q->whereNull('reason')->orWhere('reason', '!=', 'license_installment');
            });
        }
        $vouchers = $vouchers->merge($voucherQuery->get())->unique('id')->values();

        $transactionQuery = Transactions::withTrashed()
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
            });
        $transactions = $transactions->merge($transactionQuery->get())->unique('id')->values();

        return [$vouchers, $transactions];
    }

    /**
     * Hard-delete payment vouchers/transactions, rebuild ledgers, mark expense unpaid.
     * Avoids recycle-bin restore conflicts when the entry is paid again later.
     */
    public static function purgePaidExpensePayment(
        Model $expense,
        string $referenceType,
        Collection $vouchers,
        Collection $transactions
    ): void {
        $accountIds = $transactions->pluck('account_id')->filter()->unique()->values();
        $billingMonth = $expense->billing_month;

        $wasBypassing = static::isBypassing();
        static::bypass(true);
        try {
            foreach ($vouchers as $voucher) {
                if (method_exists($voucher, 'forceDelete')) {
                    $voucher->forceDelete();
                } else {
                    $voucher->delete();
                }
            }

            foreach ($transactions as $transaction) {
                if (method_exists($transaction, 'forceDelete')) {
                    $transaction->forceDelete();
                } else {
                    $transaction->delete();
                }
            }
        } finally {
            if (! $wasBypassing) {
                static::bypass(false);
            }
        }

        if ($billingMonth) {
            foreach ($accountIds as $accountId) {
                static::recalculateLedgerAfterVoucherDeletion((int) $accountId, $billingMonth);
            }
        }
        static::recalculateExpenseRiderLedger($expense);

        $expense->payment_status = 'unpaid';
        if (Schema::hasColumn($expense->getTable(), 'deleted_by') && $expense->deleted_by) {
            $expense->deleted_by = null;
        }
        $expense->save();
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

        // Payment vouchers (PV): reverse invoice/cheque links; soft-delete payment if still active.
        $relatedPayment = \App\Models\Payment::query()
            ->withTrashed()
            ->where('voucher_id', $voucher->id)
            ->first();

        if (! $relatedPayment) {
            foreach ($deleteRequest->cascaded_records ?? [] as $cascaded) {
                if (($cascaded['type'] ?? null) !== \App\Models\Payment::class || empty($cascaded['id'])) {
                    continue;
                }
                $relatedPayment = \App\Models\Payment::withTrashed()->find($cascaded['id']);
                if ($relatedPayment) {
                    break;
                }
            }
        }

        if ($relatedPayment) {
            try {
                PaymentDeletionService::executeAfterVoucherApproved($relatedPayment, $admin?->id);
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
