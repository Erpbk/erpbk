<?php

namespace App\Services\Voucher;

use App\Models\Company;
use App\Models\VoucherSignatureConfig;
use App\Models\VoucherSignatureSlot;
use App\Services\Company\UserStampSignatureService;
use App\Support\CompanyContext;
use Illuminate\Support\Collection;

class VoucherSignatureResolver
{
    public function __construct(private UserStampSignatureService $stampService)
    {
    }

    /**
     * Print layout (header + signature position) for a voucher type.
     *
     * @return array{header: array<string, mixed>, signature_position: string}|null
     */
    public function layoutFor(string $voucherTypeCode, ?int $companyId = null): ?array
    {
        $config = $this->configFor($voucherTypeCode, $companyId);
        if (!$config) {
            return null;
        }

        return [
            // Only emit header overrides when explicitly saved (avoid restyling legacy configs).
            'header' => $config->header_settings !== null
                ? $config->resolvedHeaderSettings()
                : null,
            'signature_position' => $config->resolvedSignaturePosition(),
        ];
    }

    /**
     * Unpack layout for Blade document bodies.
     *
     * @return array{0: ?array, 1: ?array, 2: string}
     */
    public function layoutPartsFor(string $voucherTypeCode, ?int $companyId = null): array
    {
        $layout = $this->layoutFor($voucherTypeCode, $companyId);

        return [
            $layout,
            is_array($layout['header'] ?? null)
                ? VoucherSignatureConfig::normalizeHeaderSettings($layout['header'])
                : null,
            ($layout['signature_position'] ?? VoucherSignatureConfig::POSITION_AFTER) === VoucherSignatureConfig::POSITION_BOTTOM
                ? VoucherSignatureConfig::POSITION_BOTTOM
                : VoucherSignatureConfig::POSITION_AFTER,
        ];
    }

    /**
     * Active signature slots for a voucher type, or empty when unconfigured.
     *
     * @return Collection<int, array{role_label: string, display_name: ?string, designation: ?string, sort_order: int, stamp_url: ?string, signature_url: ?string, user_id: ?int}>
     */
    public function slotsFor(string $voucherTypeCode, ?int $companyId = null, ?string $module = null, string $docKind = 'v'): Collection
    {
        $code = strtoupper(trim($voucherTypeCode));
        if ($code === '') {
            return collect();
        }

        $companyId = $companyId ?? CompanyContext::id();
        $company = $companyId ? Company::query()->find($companyId) : null;

        $config = $this->configFor($code, $companyId);
        if (!$config || $config->activeSlots->isEmpty()) {
            return collect();
        }

        return $config->activeSlots
            ->sortBy([
                ['sort_order', 'asc'],
                ['id', 'asc'],
            ])
            ->values()
            ->map(fn (VoucherSignatureSlot $slot) => $this->resolveSlot($slot, $company, $module, $docKind))
            ->filter(fn (array $slot) => empty($slot['_blocked']))
            ->map(function (array $slot) {
                unset($slot['_blocked']);

                return $slot;
            })
            ->values();
    }

    protected function configFor(string $voucherTypeCode, ?int $companyId = null): ?VoucherSignatureConfig
    {
        $code = strtoupper(trim($voucherTypeCode));
        if ($code === '') {
            return null;
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

        return $query->first();
    }

    /**
     * Stamp/signature assets for the currently logged-in user (if permitted for module/doc kind).
     */
    public function authUserAssets(?string $module = null, string $docKind = 'v'): ?array
    {
        $stamp = $this->stampService->forAuthUser();
        if (! $stamp) {
            return null;
        }
        if ($module && ! $this->stampService->canApply($stamp, $module, $docKind)) {
            return null;
        }

        return $stamp;
    }

    /**
     * @return array{role_label: string, display_name: ?string, designation: ?string, sort_order: int, stamp_url: ?string, signature_url: ?string, user_id: ?int, _blocked?: bool}
     */
    protected function resolveSlot(VoucherSignatureSlot $slot, ?Company $company, ?string $module, string $docKind): array
    {
        $displayName = null;
        $designation = trim((string) ($slot->designation_override ?? ''));
        $stampUrl = null;
        $signatureUrl = null;
        $userId = null;
        $blocked = false;

        switch ($slot->assignee_type) {
            case VoucherSignatureSlot::ASSIGNEE_DEPARTMENT:
                $displayName = trim((string) ($slot->department->name ?? ''));
                break;

            case VoucherSignatureSlot::ASSIGNEE_USER:
                $user = $slot->user;
                $userId = $user?->id;
                $displayName = $user
                    ? trim((string) ($user->name ?: trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''))))
                    : '';
                if ($company && $userId) {
                    $stamp = $this->stampService->forUser($company, (int) $userId);
                    if ($stamp) {
                        if ($module && ! $this->stampService->canApply($stamp, $module, $docKind)) {
                            $blocked = true;
                        }
                        $stampUrl = $stamp['stampimg'] ?? null;
                        $signatureUrl = $stamp['signimg'] ?? null;
                        if ($designation === '' && ! empty($stamp['sigdes'])) {
                            $designation = (string) $stamp['sigdes'];
                        }
                        if ($displayName === '' && ! empty($stamp['signame'])) {
                            $displayName = (string) $stamp['signame'];
                        }
                    }
                }
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
            'stamp_url' => $stampUrl,
            'signature_url' => $signatureUrl,
            'user_id' => $userId ? (int) $userId : null,
            '_blocked' => $blocked,
        ];
    }
}
