<?php

namespace App\Http\Controllers;

use App\Models\NotificationPreference;
use App\Models\NotificationRule;
use App\Models\User;
use App\Services\Notifications\CompanyNotificationChannels;
use App\Services\Notifications\NotificationModuleAccess;
use App\Services\Notifications\NotificationRuleService;
use App\Services\Notifications\NotificationTypeRegistry;
use App\Support\CompanyContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationPreferencesSettingsController extends Controller
{
    public function edit(
        string $company_slug,
        NotificationTypeRegistry $registry,
        NotificationModuleAccess $moduleAccess,
        NotificationRuleService $ruleService
    ) {
        /** @var User $user */
        $user = Auth::user();
        abort_unless($user, 403);

        $companyId = (int) CompanyContext::id();
        abort_if($companyId <= 0, 403);

        $ruleService->ensureDefaultsForCompany($companyId);
        $enabledTypeKeys = $this->enabledTypeKeys($companyId);

        $availableChannels = CompanyNotificationChannels::enabledFor($companyId);
        $typesByModule = $this->filterEnabledTypes(
            $moduleAccess->typesByModuleFor($user),
            $enabledTypeKeys
        );
        $accessibleKeys = array_values(array_intersect(
            $moduleAccess->typeKeysFor($user),
            $enabledTypeKeys
        ));

        $prefs = NotificationPreference::query()
            ->where('company_id', $companyId)
            ->where('user_id', $user->id)
            ->whereIn('type_key', $accessibleKeys !== [] ? $accessibleKeys : ['__none__'])
            ->get()
            ->groupBy('type_key');

        $matrix = [];
        foreach ($typesByModule as $moduleTypes) {
            foreach ($moduleTypes as $typeKey => $def) {
                $row = [];
                foreach ($availableChannels as $channel) {
                    $existing = ($prefs->get($typeKey) ?? collect())
                        ->firstWhere('channel', $channel);
                    $row[$channel] = $existing === null ? true : (bool) $existing->enabled;
                }
                $matrix[$typeKey] = $row;
            }
        }

        $channelLabels = [
            'in_app' => __('In-App'),
            'email' => __('Email'),
        ];
        $channelIcons = [
            'in_app' => 'ti-bell',
            'email' => 'ti-mail',
        ];
        $triggerLabels = [
            'scheduled' => __('Scheduled'),
            'condition' => __('Condition'),
            'event' => __('Event'),
        ];

        return view('settings.notification_preferences.edit', compact(
            'typesByModule',
            'registry',
            'availableChannels',
            'channelLabels',
            'channelIcons',
            'triggerLabels',
            'matrix'
        ));
    }

    public function update(
        Request $request,
        string $company_slug,
        NotificationTypeRegistry $registry,
        NotificationModuleAccess $moduleAccess,
        NotificationRuleService $ruleService
    ) {
        /** @var User $user */
        $user = Auth::user();
        abort_unless($user, 403);

        $companyId = (int) CompanyContext::id();
        abort_if($companyId <= 0, 403);

        $ruleService->ensureDefaultsForCompany($companyId);
        $availableChannels = CompanyNotificationChannels::enabledFor($companyId);
        $typeKeys = array_values(array_intersect(
            $moduleAccess->typeKeysFor($user),
            $this->enabledTypeKeys($companyId)
        ));

        $validated = $request->validate([
            'prefs' => 'nullable|array',
            'prefs.*' => 'nullable|array',
            'prefs.*.*' => ['nullable', 'boolean'],
        ]);

        $submitted = is_array($validated['prefs'] ?? null) ? $validated['prefs'] : [];

        foreach ($typeKeys as $typeKey) {
            foreach ($availableChannels as $channel) {
                $enabled = ! empty($submitted[$typeKey][$channel]);

                NotificationPreference::query()->updateOrCreate(
                    [
                        'company_id' => $companyId,
                        'user_id' => $user->id,
                        'type_key' => $typeKey,
                        'channel' => $channel,
                    ],
                    [
                        'enabled' => $enabled,
                    ]
                );
            }
        }

        return redirect()
            ->route('settings-panel.notification-preferences.edit')
            ->with('success', __('Notification preferences saved.'));
    }

    /**
     * @return list<string>
     */
    private function enabledTypeKeys(int $companyId): array
    {
        return NotificationRule::query()
            ->where('company_id', $companyId)
            ->where('enabled', true)
            ->pluck('type_key')
            ->map(fn ($key) => (string) $key)
            ->values()
            ->all();
    }

    /**
     * @param  array<string, array<string, array<string, mixed>>>  $typesByModule
     * @param  list<string>  $enabledTypeKeys
     * @return array<string, array<string, array<string, mixed>>>
     */
    private function filterEnabledTypes(array $typesByModule, array $enabledTypeKeys): array
    {
        if ($enabledTypeKeys === []) {
            return [];
        }

        $enabledLookup = array_fill_keys($enabledTypeKeys, true);
        $out = [];
        foreach ($typesByModule as $moduleKey => $moduleTypes) {
            $filtered = [];
            foreach ($moduleTypes as $typeKey => $def) {
                if (isset($enabledLookup[$typeKey])) {
                    $filtered[$typeKey] = $def;
                }
            }
            if ($filtered !== []) {
                $out[$moduleKey] = $filtered;
            }
        }

        return $out;
    }
}
