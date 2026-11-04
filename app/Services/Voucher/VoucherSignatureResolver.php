<?php

namespace App\Services\Voucher;

use App\Models\VoucherSignatureConfig;
use App\Models\VoucherSignatureSlot;
use App\Support\CompanyContext;
use Illuminate\Support\Collection;

class VoucherSignatureResolver
{
    /**
     * Active signature slots for a voucher type, or empty when unconfigured.
     *
     * @return Collection<int, array{role_label: string, display_name: ?string, designation: ?string, sort_order: int}>
     */
    public function slotsFor(string $voucherTypeCode, ?int $companyId = null): Collection
    {
        $code = strtoupper(trim($voucherTypeCode));
        if ($code === '') {
            return collect();
        }

        $companyId = $companyId ?? CompanyContext::id();

        $query = VoucherSignatureConfig::query()
            ->where('voucher_type_code', $code)
            ->with([
                'activeSlots.department',
                'activeSlots.user',
                'activeSlots.employee',
            ]);

        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        $config = $query->first();
        if (!$config || $config->activeSlots->isEmpty()) {
            return collect();
        }

        return $config->activeSlots
            ->sortBy([
                ['sort_order', 'asc'],
                ['id', 'asc'],
            ])
            ->values()
            ->map(fn (VoucherSignatureSlot $slot) => $this->resolveSlot($slot));
    }

    /**
     * @return array{role_label: string, display_name: ?string, designation: ?string, sort_order: int}
     */
    protected function resolveSlot(VoucherSignatureSlot $slot): array
    {
        $displayName = null;
        $designation = trim((string) ($slot->designation_override ?? ''));

        switch ($slot->assignee_type) {
            case VoucherSignatureSlot::ASSIGNEE_DEPARTMENT:
                $displayName = trim((string) ($slot->department->name ?? ''));
                break;

            case VoucherSignatureSlot::ASSIGNEE_USER:
                $user = $slot->user;
                $displayName = $user
                    ? trim((string) ($user->name ?: trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''))))
                    : '';
                break;

            case VoucherSignatureSlot::ASSIGNEE_EMPLOYEE:
                $employee = $slot->employee;
                $displayName = $employee ? trim((string) ($employee->name ?? '')) : '';
                if ($designation === '' && $employee) {
                    $designation = trim((string) ($employee->designation ?? ''));
                }
                break;

            case VoucherSignatureSlot::ASSIGNEE_LABEL_ONLY:
            default:
                break;
        }

        return [
            'role_label' => trim((string) $slot->role_label) ?: 'Signature',
            'display_name' => $displayName !== '' ? $displayName : null,
            'designation' => $designation !== '' ? $designation : null,
            'sort_order' => (int) $slot->sort_order,
        ];
    }
}
