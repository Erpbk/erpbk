<?php

namespace App\Services\Company;

use App\Helpers\IConstants;
use App\Models\AdminCompany;
use App\Models\Company;
use App\Models\Settings;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Propagates a verified company contact email change across related records.
 */
class CompanyContactEmailSync
{
    public function apply(Company $company, ?string $oldEmail, ?string $newEmail): void
    {
        $old = strtolower(trim((string) $oldEmail));
        $new = strtolower(trim((string) $newEmail));

        if ($new === '' || $old === $new) {
            return;
        }

        $this->syncSettingsEmail($company, $new);
        AdminCompany::syncFromCentralCompany($company);
        $this->syncUserEmails((int) $company->id, $old, $new);
    }

    private function syncSettingsEmail(Company $company, string $newEmail): void
    {
        Settings::query()->updateOrCreate(
            [
                'name' => 'company_email',
                'company_id' => $company->id,
            ],
            [
                'name' => 'company_email',
                'value' => $newEmail,
                'company_id' => $company->id,
            ]
        );
    }

    private function syncUserEmails(int $companyId, string $oldEmail, string $newEmail): void
    {
        DB::transaction(function () use ($companyId, $oldEmail, $newEmail) {
            $superAdminIds = User::withoutGlobalScope('company')
                ->where('company_id', $companyId)
                ->whereHas('roles', fn ($query) => $query->where('name', IConstants::ROLE_SUPER_ADMIN))
                ->pluck('id');

            if ($superAdminIds->isNotEmpty()) {
                User::withoutGlobalScope('company')
                    ->whereIn('id', $superAdminIds)
                    ->update(['email' => $newEmail]);
            }

            if ($oldEmail !== '') {
                User::withoutGlobalScope('company')
                    ->where('company_id', $companyId)
                    ->whereNotIn('id', $superAdminIds)
                    ->whereRaw('LOWER(TRIM(email)) = ?', [$oldEmail])
                    ->update(['email' => $newEmail]);
            }
        });
    }
}
