<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminCompany;
use App\Models\Company;
use App\Services\Notifications\CompanyNotificationChannels;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminCompanyNotificationChannelsController extends Controller
{
    public function edit(AdminCompany $company)
    {
        // Channels are edited via modal on the companies list/show pages.
        return redirect()
            ->route('admin.companies.index')
            ->with('open_channels_modal', $company->id);
    }

    public function update(Request $request, AdminCompany $company)
    {
        $allChannels = CompanyNotificationChannels::allChannels();
        $validated = $request->validate([
            'enabled' => 'nullable|array',
            'enabled.*' => ['string', Rule::in($allChannels)],
        ]);

        $central = Company::query()->find($company->id);
        if (! $central) {
            return redirect()
                ->back()
                ->with('error', __('Central company record not found. Approve the company first.'));
        }

        CompanyNotificationChannels::saveForCompany($central, $validated['enabled'] ?? []);
        AdminCompany::syncFromCentralCompany($central->fresh());

        return redirect()
            ->back()
            ->with('success', __('Notification channels updated for :name.', ['name' => $company->name]));
    }
}
