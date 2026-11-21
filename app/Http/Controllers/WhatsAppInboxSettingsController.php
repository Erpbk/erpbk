<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\WhatsAppConnection;
use App\Models\WhatsAppUnknownInboxSetting;
use App\Services\WhatsApp\WhatsAppConnectionService;
use App\Support\CompanyContext;
use App\Support\WhatsApp\WhatsAppCompanyAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class WhatsAppInboxSettingsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request, WhatsAppConnectionService $connections)
    {
        $user = $request->user();
        abort_unless($user && method_exists($user, 'isAdmin') && $user->isAdmin(), 403);

        $companyId = (int) CompanyContext::id();
        abort_unless(WhatsAppCompanyAccess::available($companyId), 403);

        $numbers = $connections->getActiveCompanyConnections($companyId);
        $numbers->load('users');
        $settings = WhatsAppUnknownInboxSetting::query()
            ->where('company_id', $companyId)
            ->get()
            ->keyBy('whatsapp_connection_id');

        $users = $this->companyUsersQuery($companyId)
            ->orderBy('name')
            ->get(['id', 'name', 'first_name', 'last_name', 'email', 'username']);

        return view('whatsapp_inbox.settings', [
            'numbers' => $numbers,
            'settings' => $settings,
            'users' => $users,
        ]);
    }

    public function update(Request $request, WhatsAppConnectionService $connections)
    {
        $user = $request->user();
        abort_unless($user && method_exists($user, 'isAdmin') && $user->isAdmin(), 403);

        $companyId = (int) CompanyContext::id();
        abort_unless(WhatsAppCompanyAccess::available($companyId), 403);

        $validated = $request->validate([
            'assignees' => 'nullable|array',
            'assignees.*' => 'nullable|integer|min:1',
            'allotments' => 'nullable|array',
            'allotments.*' => 'nullable|array',
            'allotments.*.*' => 'integer|min:1',
        ]);

        $companyUserIds = $this->companyUsersQuery($companyId)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
        $companyUserIdSet = array_fill_keys($companyUserIds, true);

        $numbers = $connections->getActiveCompanyConnections($companyId);
        $assignees = $validated['assignees'] ?? [];
        $allotments = $validated['allotments'] ?? [];

        DB::transaction(function () use ($companyId, $numbers, $assignees, $allotments, $companyUserIdSet) {
            foreach ($numbers as $number) {
                $connectionId = (int) $number->id;

                $rawUserIds = $allotments[$connectionId] ?? $allotments[(string) $connectionId] ?? [];
                if (! is_array($rawUserIds)) {
                    $rawUserIds = [];
                }
                $userIds = array_values(array_unique(array_filter(
                    array_map('intval', $rawUserIds),
                    fn (int $id) => $id > 0 && isset($companyUserIdSet[$id])
                )));

                /** @var WhatsAppConnection $number */
                $number->users()->sync($userIds);

                $assigneeRaw = $assignees[$connectionId] ?? $assignees[(string) $connectionId] ?? null;
                $assigneeId = $assigneeRaw ? (int) $assigneeRaw : null;
                if ($assigneeId && ! isset($companyUserIdSet[$assigneeId])) {
                    $assigneeId = null;
                }

                WhatsAppUnknownInboxSetting::query()->updateOrCreate(
                    [
                        'company_id' => $companyId,
                        'whatsapp_connection_id' => $connectionId,
                    ],
                    ['user_id' => $assigneeId]
                );
            }
        });

        return redirect()
            ->route('settings-panel.whatsapp-settings.index', [
                'company_slug' => $request->route('company_slug'),
            ])
            ->with('success', 'WhatsApp settings saved.');
    }

    private function companyUsersQuery(int $companyId)
    {
        return User::query()->when(
            Schema::hasColumn('users', 'company_id'),
            fn ($q) => $q->where('company_id', $companyId)
        );
    }
}
