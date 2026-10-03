<?php

namespace App\Http\Controllers;

use App\Models\EmailAccount;
use App\Models\NotificationRule;
use App\Models\User;
use App\Services\Notifications\CompanyNotificationChannels;
use App\Services\Notifications\NotificationModuleAccess;
use App\Services\Notifications\NotificationRuleService;
use App\Services\Notifications\NotificationTypeRegistry;
use App\Services\Notifications\RecipientBuckets;
use App\Support\CompanyContext;
use App\Support\RoleFieldAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class NotificationRulesSettingsController extends Controller
{
    public function index(
        string $company_slug,
        NotificationTypeRegistry $registry,
        NotificationRuleService $ruleService,
        NotificationModuleAccess $moduleAccess
    ) {
        abort_unless(auth()->user()?->isAdmin(), 403);

        $companyId = (int) CompanyContext::id();
        abort_if($companyId <= 0, 403);

        $rules = $ruleService->ensureDefaultsForCompany($companyId);
        $rulesByKey = collect($rules)->keyBy('type_key');
        $typesByModule = $moduleAccess->typesByModuleFor(auth()->user());
        $availableChannels = CompanyNotificationChannels::enabledFor($companyId);
        $channelLabels = [
            'in_app' => __('In-App'),
            'email' => __('Email'),
        ];
        $emailEntitled = in_array('email', $availableChannels, true);
        $inAppEntitled = in_array('in_app', $availableChannels, true);

        $companyUsers = User::query()
            ->where('company_id', $companyId)
            ->with(['roles.permissions', 'permissions'])
            ->orderByRaw("COALESCE(NULLIF(name, ''), email, username, id)")
            ->get(['id', 'name', 'email', 'username', 'first_name', 'last_name']);

        $usersByModule = [];
        foreach ($typesByModule as $moduleKey => $moduleTypes) {
            $usersByModule[$moduleKey] = $this->usersWithModuleAccess(
                $companyUsers,
                (string) $moduleKey,
                is_array($moduleTypes) ? $moduleTypes : []
            );
        }

        $emailAccounts = EmailAccount::query()
            ->where('company_id', $companyId)
            ->active()
            ->orderBy('email')
            ->get(['id', 'email', 'display_name', 'status']);

        $defaultFromEmail = (string) config('mail.from.address');
        $defaultFromName = (string) config('mail.from.name');

        return view('settings.notification_rules.index', compact(
            'typesByModule',
            'registry',
            'rulesByKey',
            'availableChannels',
            'channelLabels',
            'emailEntitled',
            'inAppEntitled',
            'usersByModule',
            'emailAccounts',
            'defaultFromEmail',
            'defaultFromName'
        ));
    }

    public function update(
        Request $request,
        string $company_slug,
        NotificationRule $notificationRule,
        NotificationRuleService $ruleService,
        NotificationTypeRegistry $registry
    ) {
        abort_unless(auth()->user()?->isAdmin(), 403);

        $companyId = (int) CompanyContext::id();
        abort_if((int) $notificationRule->company_id !== $companyId, 403);

        $availableChannels = CompanyNotificationChannels::enabledFor($companyId);
        $emailEntitled = in_array('email', $availableChannels, true);
        $inAppEntitled = in_array('in_app', $availableChannels, true);
        $typeDef = $registry->get($notificationRule->type_key) ?? [];
        $moduleKey = (string) ($typeDef['module'] ?? '');

        $companyUsers = User::query()
            ->where('company_id', $companyId)
            ->with(['roles.permissions', 'permissions'])
            ->get(['id', 'name', 'email', 'username', 'first_name', 'last_name']);

        $allowedUserIds = $this->usersWithModuleAccess(
            $companyUsers,
            $moduleKey,
            [$notificationRule->type_key => $typeDef]
        )->pluck('id')->map(fn ($id) => (int) $id)->all();

        $accountIds = EmailAccount::query()
            ->where('company_id', $companyId)
            ->active()
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $enabled = $request->boolean('enabled');

        $validated = $request->validate([
            'enabled' => 'nullable|boolean',
            'days_before' => 'nullable|array',
            'days_before.*' => 'integer|min:0|max:365',
            'both_user_ids' => 'nullable|array',
            'both_user_ids.*' => ['integer', Rule::in($allowedUserIds)],
            'in_app_only_user_ids' => 'nullable|array',
            'in_app_only_user_ids.*' => ['integer', Rule::in($allowedUserIds)],
            'email_only_user_ids' => 'nullable|array',
            'email_only_user_ids.*' => ['integer', Rule::in($allowedUserIds)],
            'emails' => 'nullable|array',
            'emails.*' => 'nullable|string|max:255',
            'email_account_id' => ['nullable', Rule::in(array_merge(['', '0', 0], $accountIds))],
            'statuses' => 'nullable|array',
            'statuses.*' => ['string', Rule::in($typeDef['status_options'] ?? [])],
        ]);

        $buckets = RecipientBuckets::normalize([
            'both_user_ids' => $inAppEntitled || $emailEntitled ? ($validated['both_user_ids'] ?? []) : [],
            'in_app_only_user_ids' => $inAppEntitled ? ($validated['in_app_only_user_ids'] ?? []) : [],
            'email_only_user_ids' => $emailEntitled ? ($validated['email_only_user_ids'] ?? []) : [],
            'emails' => $emailEntitled ? ($validated['emails'] ?? []) : [],
        ]);

        // Parse free-text emails field (may arrive as a single textarea string).
        if ($emailEntitled && $request->filled('emails_text')) {
            $raw = preg_split('/\s+/', (string) $request->input('emails_text'), -1, PREG_SPLIT_NO_EMPTY) ?: [];
            $buckets = RecipientBuckets::normalize(array_merge($buckets, [
                'emails' => array_merge($buckets['emails'], $raw),
            ]));
        }

        if ($enabled && ! RecipientBuckets::hasAnyRecipient($buckets)) {
            throw ValidationException::withMessages([
                'both_user_ids' => __('Select at least one recipient before enabling this rule.'),
            ]);
        }

        $config = is_array($notificationRule->config) ? $notificationRule->config : ($typeDef['default_config'] ?? []);
        if (($typeDef['trigger'] ?? null) === 'scheduled' && array_key_exists('days_before', $validated)) {
            $days = array_values(array_unique(array_map('intval', $validated['days_before'] ?? [])));
            sort($days);
            $config['days_before'] = $days !== [] ? $days : [3];
        }

        if (! empty($typeDef['status_options'])) {
            $statuses = array_values(array_unique(array_map('strval', $validated['statuses'] ?? [])));
            $allowedStatuses = $typeDef['status_options'];
            $statuses = array_values(array_intersect($statuses, $allowedStatuses));
            $config['statuses'] = $statuses !== []
                ? $statuses
                : ($typeDef['default_config']['statuses'] ?? ['Issued']);
        }

        if (! array_key_exists('email_account_id', $validated) || $validated['email_account_id'] === null || $validated['email_account_id'] === '') {
            unset($config['email_account_id']);
        } else {
            $config['email_account_id'] = (int) $validated['email_account_id'];
        }

        $ruleService->updateRule($notificationRule, [
            'enabled' => $enabled,
            'config' => $config,
            'recipient_config' => $buckets,
        ]);

        return redirect()
            ->route('settings-panel.notification-rules.index')
            ->with('success', __('Notification rule updated.'));
    }

    public function availableChannels(string $company_slug)
    {
        abort_unless(auth()->user()?->isAdmin(), 403);
        $companyId = (int) CompanyContext::id();

        return response()->json([
            'channels' => CompanyNotificationChannels::enabledFor($companyId),
        ]);
    }

    /**
     * Users who can access the notification's module (view permission / admin),
     * or who match the type's default role/permission strategies when no module tree exists.
     *
     * @param  Collection<int, User>  $users
     * @param  array<string, array<string, mixed>>  $moduleTypes
     * @return Collection<int, User>
     */
    private function usersWithModuleAccess(Collection $users, string $moduleKey, array $moduleTypes): Collection
    {
        $entityKey = RoleFieldAccess::entityKeyFromModuleKey($moduleKey);
        if ($entityKey !== null) {
            return $users
                ->filter(fn (User $user) => RoleFieldAccess::userCanAccessModule($entityKey, $user))
                ->values();
        }

        $roles = [];
        $permissions = [];
        foreach ($moduleTypes as $typeDef) {
            $strategies = $typeDef['default_recipient_config']['strategies'] ?? [];
            if (! is_array($strategies)) {
                continue;
            }
            foreach ($strategies as $strategy) {
                $strategy = (string) $strategy;
                if (str_starts_with($strategy, 'role:')) {
                    $roles[] = substr($strategy, 5);
                } elseif (str_starts_with($strategy, 'permission:')) {
                    $permissions[] = substr($strategy, 11);
                }
            }
        }
        $roles = array_values(array_unique(array_filter($roles)));
        $permissions = array_values(array_unique(array_filter($permissions)));

        if ($roles === [] && $permissions === []) {
            return $users->values();
        }

        return $users->filter(function (User $user) use ($roles, $permissions) {
            try {
                if ($user->isAdmin()) {
                    return true;
                }
            } catch (\Throwable $e) {
                // fall through
            }

            if ($roles !== []) {
                try {
                    if ($user->hasAnyRole($roles)) {
                        return true;
                    }
                } catch (\Throwable $e) {
                    // fall through
                }
            }

            foreach ($permissions as $permission) {
                if (RoleFieldAccess::userCan($permission, $user)) {
                    return true;
                }
            }

            return false;
        })->values();
    }
}
