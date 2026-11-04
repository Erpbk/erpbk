<?php

namespace App\Services;

use App\Models\User;
use App\Models\VoucherApprovalEvent;
use App\Models\Vouchers;
use App\Services\VoucherApproval\VoucherApprovalLedger;
use App\Support\VoucherApprovalPermissions;
use App\Support\VoucherApprovalRegistry;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class VoucherApprovalService
{
    public function approve(Vouchers $voucher, ?User $user = null, ?string $remark = null, bool $force = false): Vouchers
    {
        $user = $user ?? Auth::user();
        if (! $user instanceof User) {
            abort(403);
        }

        return DB::transaction(function () use ($voucher, $user, $remark, $force) {
            /** @var Vouchers $locked */
            $locked = Vouchers::query()->whereKey($voucher->id)->lockForUpdate()->firstOrFail();

            if ($locked->isApprovalApproved()) {
                throw ValidationException::withMessages([
                    'approval' => 'This voucher is already approved.',
                ]);
            }

            if ($force) {
                if (! VoucherApprovalPermissions::canForceApprove($user)) {
                    abort(403, 'Unauthorized: force approve permission required.');
                }
                if ($remark === null || strlen(trim($remark)) < 10) {
                    throw ValidationException::withMessages([
                        'remark' => 'A remark of at least 10 characters is required for force approve.',
                    ]);
                }
            } else {
                VoucherApprovalPermissions::authorizeApprove($locked, $user);
            }

            $from = $locked->approval_status;
            $locked->forceFill([
                'approval_status' => 'approved',
                'approved_by' => $user->id,
                'approved_at' => now(),
                'rejected_by' => null,
                'rejected_at' => null,
                'rejection_reason' => null,
                'approval_locked_at' => now(),
            ])->save();

            VoucherApprovalLedger::restoreForVoucher($locked);

            $this->appendEvent($locked, $user, $force ? 'force_approved' : 'approved', $from, 'approved', $remark, [
                'force' => $force,
            ]);

            if (method_exists($locked, 'logActivity')) {
                $locked->logActivity($force ? 'force_approved' : 'approved', [
                    'from' => $from,
                    'to' => 'approved',
                    'remark' => $remark,
                ]);
            }

            return $locked->fresh(['approvedByUser', 'rejectedByUser', 'approvalEvents']);
        });
    }

    public function reject(Vouchers $voucher, string $reason, ?User $user = null): Vouchers
    {
        $user = $user ?? Auth::user();
        if (! $user instanceof User) {
            abort(403);
        }

        $reason = trim($reason);
        if (strlen($reason) < 10) {
            throw ValidationException::withMessages([
                'reason' => 'Rejection reason must be at least 10 characters.',
            ]);
        }

        return DB::transaction(function () use ($voucher, $user, $reason) {
            /** @var Vouchers $locked */
            $locked = Vouchers::query()->whereKey($voucher->id)->lockForUpdate()->firstOrFail();

            if ($locked->isApprovalRejected()) {
                throw ValidationException::withMessages([
                    'approval' => 'This voucher is already rejected.',
                ]);
            }

            if ($locked->isApprovalApproved()) {
                throw ValidationException::withMessages([
                    'approval' => 'Approved vouchers cannot be rejected. Use revise/reopen instead.',
                ]);
            }

            VoucherApprovalPermissions::authorizeReject($locked, $user);

            $from = $locked->approval_status;
            $locked->forceFill([
                'approval_status' => 'rejected',
                'rejected_by' => $user->id,
                'rejected_at' => now(),
                'rejection_reason' => $reason,
                'approved_by' => null,
                'approved_at' => null,
                'approval_locked_at' => null,
            ])->save();

            VoucherApprovalLedger::holdForVoucher($locked);

            $this->appendEvent($locked, $user, 'rejected', $from, 'rejected', $reason);

            if (method_exists($locked, 'logActivity')) {
                $locked->logActivity('rejected', [
                    'from' => $from,
                    'to' => 'rejected',
                    'reason' => $reason,
                ]);
            }

            return $locked->fresh(['approvedByUser', 'rejectedByUser', 'approvalEvents']);
        });
    }

    public function resubmit(Vouchers $voucher, ?User $user = null, ?string $remark = null): Vouchers
    {
        $user = $user ?? Auth::user();
        if (! $user instanceof User) {
            abort(403);
        }

        return DB::transaction(function () use ($voucher, $user, $remark) {
            /** @var Vouchers $locked */
            $locked = Vouchers::query()->whereKey($voucher->id)->lockForUpdate()->firstOrFail();

            if (! $locked->isApprovalRejected()) {
                throw ValidationException::withMessages([
                    'approval' => 'Only rejected vouchers can be resubmitted.',
                ]);
            }

            $creatorId = (int) ($locked->Created_By ?? 0);
            $isCreator = $creatorId === (int) $user->id;
            $isAdmin = method_exists($user, 'isAdmin') && $user->isAdmin();
            if (! $isCreator && ! $isAdmin && ! VoucherApprovalPermissions::canReopen($user)) {
                abort(403, 'Only the creator (or an authorized admin) can resubmit this voucher.');
            }

            $from = $locked->approval_status;
            $locked->forceFill([
                'approval_status' => 'pending_approval',
                'rejected_by' => null,
                'rejected_at' => null,
                // Keep rejection_reason for history; clear "current" display fields via events.
                'submitted_at' => now(),
                'approved_by' => null,
                'approved_at' => null,
                'approval_locked_at' => null,
            ])->save();

            VoucherApprovalLedger::holdForVoucher($locked);

            $this->appendEvent($locked, $user, 'resubmitted', $from, 'pending_approval', $remark);

            if (method_exists($locked, 'logActivity')) {
                $locked->logActivity('resubmitted', [
                    'from' => $from,
                    'to' => 'pending_approval',
                    'remark' => $remark,
                ]);
            }

            return $locked->fresh(['approvalEvents']);
        });
    }

    public function reopen(Vouchers $voucher, string $remark, ?User $user = null): Vouchers
    {
        $user = $user ?? Auth::user();
        if (! $user instanceof User) {
            abort(403);
        }

        if (! VoucherApprovalPermissions::canReopen($user)) {
            abort(403, 'Unauthorized: reopen permission required.');
        }

        $remark = trim($remark);
        if (strlen($remark) < 10) {
            throw ValidationException::withMessages([
                'remark' => 'A remark of at least 10 characters is required to reopen.',
            ]);
        }

        return DB::transaction(function () use ($voucher, $user, $remark) {
            /** @var Vouchers $locked */
            $locked = Vouchers::query()->whereKey($voucher->id)->lockForUpdate()->firstOrFail();

            $from = $locked->approval_status;
            $locked->forceFill([
                'approval_status' => 'pending_approval',
                'approved_by' => null,
                'approved_at' => null,
                'rejected_by' => null,
                'rejected_at' => null,
                'approval_locked_at' => null,
                'submitted_at' => now(),
            ])->save();

            VoucherApprovalLedger::holdForVoucher($locked);

            $this->appendEvent($locked, $user, 'reopened', $from, 'pending_approval', $remark);

            if (method_exists($locked, 'logActivity')) {
                $locked->logActivity('reopened', [
                    'from' => $from,
                    'to' => 'pending_approval',
                    'remark' => $remark,
                ]);
            }

            return $locked->fresh(['approvalEvents']);
        });
    }

    public function reviseApproved(Vouchers $voucher, string $remark, ?User $user = null): Vouchers
    {
        $user = $user ?? Auth::user();
        if (! $user instanceof User) {
            abort(403);
        }

        if (! VoucherApprovalPermissions::canRevise($user)) {
            abort(403, 'Unauthorized: revise permission required.');
        }

        $remark = trim($remark);
        if (strlen($remark) < 10) {
            throw ValidationException::withMessages([
                'remark' => 'A remark of at least 10 characters is required to revise an approved voucher.',
            ]);
        }

        return DB::transaction(function () use ($voucher, $user, $remark) {
            /** @var Vouchers $locked */
            $locked = Vouchers::query()->whereKey($voucher->id)->lockForUpdate()->firstOrFail();

            if (! $locked->isApprovalApproved()) {
                throw ValidationException::withMessages([
                    'approval' => 'Only approved vouchers can be revised.',
                ]);
            }

            $from = $locked->approval_status;
            $locked->forceFill([
                'approval_status' => 'pending_approval',
                'approved_by' => null,
                'approved_at' => null,
                'rejected_by' => null,
                'rejected_at' => null,
                'approval_locked_at' => null,
                'submitted_at' => now(),
            ])->save();

            VoucherApprovalLedger::holdForVoucher($locked);

            $this->appendEvent($locked, $user, 'revised', $from, 'pending_approval', $remark);

            if (method_exists($locked, 'logActivity')) {
                $locked->logActivity('revised', [
                    'from' => $from,
                    'to' => 'pending_approval',
                    'remark' => $remark,
                ]);
            }

            return $locked->fresh(['approvalEvents']);
        });
    }

    public function logPermissionChange(
        User $actor,
        array $details,
        ?string $remark = null
    ): void {
        if (! VoucherApprovalRegistry::enabled()) {
            return;
        }

        VoucherApprovalEvent::query()->create([
            'company_id' => $actor->company_id ?? null,
            'approvable_type' => User::class,
            'approvable_id' => $actor->id,
            'action' => 'permission_change',
            'from_status' => null,
            'to_status' => null,
            'user_id' => $actor->id,
            'user_name' => $actor->name,
            'remark' => $remark ?? 'Voucher approval permission assignment changed',
            'ip_address' => request()?->ip(),
            'details' => $details,
        ]);
    }

    protected function appendEvent(
        Vouchers $voucher,
        User $user,
        string $action,
        ?string $from,
        ?string $to,
        ?string $remark = null,
        array $extra = []
    ): VoucherApprovalEvent {
        return $voucher->approvalEvents()->create([
            'company_id' => $voucher->company_id ?? null,
            'action' => $action,
            'from_status' => $from,
            'to_status' => $to,
            'user_id' => $user->id,
            'user_name' => $user->name,
            'remark' => $remark,
            'ip_address' => request()?->ip(),
            'details' => array_merge([
                'voucher_type' => $voucher->voucher_type,
                'trans_code' => $voucher->trans_code,
                'amount' => $voucher->amount,
            ], $extra),
        ]);
    }
}
