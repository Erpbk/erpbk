<?php

namespace App\Http\Controllers;

use App\Jobs\WhatsApp\SendWhatsAppComposeJob;
use App\Models\User;
use App\Models\WhatsAppMessage;
use App\Services\WhatsApp\WhatsAppConnectionService;
use App\Services\WhatsApp\WhatsAppInboxService;
use App\Services\WhatsApp\WhatsAppMediaService;
use App\Support\CompanyContext;
use App\Support\PhoneNumber;
use App\Support\WhatsApp\WhatsAppCompanyAccess;
use Illuminate\Http\Request;

class WhatsAppInboxController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request, WhatsAppInboxService $inbox)
    {
        $user = $request->user();
        abort_unless($user && $user->can('whatsapp_view'), 403);

        $companyId = (int) CompanyContext::id();
        abort_unless(WhatsAppCompanyAccess::available($companyId), 403);

        $type = $request->query('type');
        $activeType = (is_string($type) && $type !== '' && $type !== 'all') ? $type : 'all';
        // Polls can skip filter counts (distinct scan); full page / filter clicks still need them.
        $includeFilters = ! in_array(strtolower((string) $request->query('include_filters', '1')), ['0', 'false', 'no'], true);
        $filters = $includeFilters ? $inbox->typeFilters($user, $companyId) : null;
        $conversations = $inbox->listConversations(
            $user,
            $companyId,
            $activeType === 'all' ? null : $activeType,
            $request->query('q'),
            $request->query('participant_id') ? (int) $request->query('participant_id') : null
        );

        if ($request->wantsJson() || $request->ajax()) {
            $payload = ['conversations' => $conversations];
            if ($filters !== null) {
                $payload['filters'] = $filters;
            }

            return response()->json($payload);
        }

        if ($filters === null) {
            $filters = $inbox->typeFilters($user, $companyId);
        }

        return view('whatsapp_inbox.index', [
            'filters' => $filters,
            'conversations' => $conversations,
            'activeType' => $activeType,
            'canCreate' => $user->can('whatsapp_create'),
            'canEdit' => $user->can('whatsapp_edit'),
            'canDelete' => $user->can('whatsapp_delete'),
            'transferUsers' => $this->transferCandidates($companyId, (int) $user->id),
        ]);
    }

    public function thread(Request $request, WhatsAppInboxService $inbox)
    {
        $user = $request->user();
        abort_unless($user && $user->can('whatsapp_view'), 403);
        $companyId = (int) CompanyContext::id();
        abort_unless(WhatsAppCompanyAccess::available($companyId), 403);

        // Read peerPhone from the route by name. Do NOT type-hint it as a method
        // argument: under app/{company_slug}/..., Laravel passes route params by
        // position after DI and company_slug would be injected as $peerPhone.
        $peerPhone = (string) $request->route('peerPhone', '');
        abort_unless($peerPhone !== '', 404);

        $afterId = $request->query('after_id') ? (int) $request->query('after_id') : null;
        $participantType = $request->query('participant_type');
        $participantType = is_string($participantType) && $participantType !== '' && $participantType !== 'unknown'
            ? $participantType
            : null;
        $participantId = $request->query('participant_id') ? (int) $request->query('participant_id') : null;
        if (! $participantType) {
            $participantId = null;
        }

        $payload = $inbox->threadPayload(
            $user,
            $companyId,
            $peerPhone,
            $afterId,
            $participantType,
            $participantId
        );

        return response()->json($payload);
    }

    public function searchParticipants(Request $request)
    {
        $user = $request->user();
        abort_unless($user && $user->can('whatsapp_view'), 403);
        $companyId = (int) CompanyContext::id();
        abort_unless(WhatsAppCompanyAccess::available($companyId), 403);

        $type = (string) $request->query('type', '');
        $q = trim((string) $request->query('q', ''));
        if ($type === '' || $type === 'all' || $q === '' || $type === 'unknown') {
            return response()->json(['results' => []]);
        }

        $query = \App\Support\WhatsApp\WhatsAppRecipientTypes::baseQuery($type);
        if (! $query) {
            return response()->json(['results' => []]);
        }

        if (\Illuminate\Support\Facades\Schema::hasColumn($query->getModel()->getTable(), 'company_id')) {
            $query->where('company_id', $companyId);
        }

        $like = '%'.$q.'%';
        $query->where(function ($inner) use ($like) {
            $inner->where('name', 'like', $like);
        })->limit(20);

        $results = $query->get()->map(fn ($m) => [
            'id' => $m->getKey(),
            'label' => \App\Support\WhatsApp\WhatsAppRecipientTypes::displayName($m),
            'trashed' => \App\Support\WhatsApp\WhatsAppRecipientTypes::isTrashed($m),
        ])->values();

        return response()->json(['results' => $results]);
    }

    public function send(
        Request $request,
        WhatsAppInboxService $inbox,
        WhatsAppConnectionService $connections,
        WhatsAppMediaService $media
    ) {
        $user = $request->user();
        abort_unless($user && $user->can('whatsapp_create'), 403);
        $companyId = (int) CompanyContext::id();
        abort_unless(WhatsAppCompanyAccess::available($companyId), 403);

        $validated = $request->validate([
            'peer_phone' => 'required|string|max:32',
            'whatsapp_connection_id' => 'required|integer|min:1',
            'body' => 'nullable|string|max:4096',
            'file' => 'nullable|file',
            'participant_type' => 'nullable|string|max:64',
            'participant_id' => 'nullable|integer|min:1',
        ]);

        $hasFile = $request->hasFile('file');
        $body = trim((string) ($validated['body'] ?? ''));
        if (! $hasFile && $body === '') {
            return response()->json(['ok' => false, 'message' => 'Message body or file is required.'], 422);
        }

        $to = PhoneNumber::toWhatsAppDigits($validated['peer_phone']);
        abort_unless(PhoneNumber::isValidWhatsAppDigits($to), 422, 'Invalid phone.');

        $connectionId = (int) $validated['whatsapp_connection_id'];
        $prepared = $connections->prepareUserConnection($user, $connectionId);
        if (! ($prepared['ready'] ?? false)) {
            return response()->json([
                'ok' => false,
                'message' => $prepared['message'] ?? 'Cannot use this number.',
            ], (int) ($prepared['status'] ?? 403));
        }

        if (! $inbox->hasOpenWindow($companyId, (string) $to, $connectionId)) {
            return response()->json([
                'ok' => false,
                'message' => 'The 24-hour messaging window is closed for this number.',
            ], 422);
        }

        $participantType = ! empty($validated['participant_type']) && ($validated['participant_type'] !== 'unknown')
            ? (string) $validated['participant_type']
            : null;
        $participantId = $participantType && ! empty($validated['participant_id'])
            ? (int) $validated['participant_id']
            : null;

        if ($hasFile) {
            $stored = $media->storeOutboundUpload($request->file('file'), $companyId);
            if (! ($stored['ok'] ?? false)) {
                return response()->json(['ok' => false, 'message' => $stored['error'] ?? 'Invalid file.'], 422);
            }

            SendWhatsAppComposeJob::dispatch(
                $companyId,
                (int) $user->id,
                $connectionId,
                $to,
                'media',
                $body !== '' ? $body : null,
                null,
                'en',
                [],
                false,
                $participantType,
                $participantId,
                $stored['path'],
                $stored['mime'],
                $stored['filename'],
                $stored['type'],
                $stored['size']
            )->onQueue('notifications');
        } else {
            SendWhatsAppComposeJob::dispatch(
                $companyId,
                (int) $user->id,
                $connectionId,
                $to,
                'text',
                $body,
                null,
                'en',
                [],
                false,
                $participantType,
                $participantId
            )->onQueue('notifications');
        }

        return response()->json(['ok' => true, 'message' => 'Message queued.']);
    }

    public function downloadMedia(
        Request $request,
        WhatsAppMessage $message,
        WhatsAppMediaService $media
    ) {
        $user = $request->user();
        abort_unless($user && $user->can('whatsapp_view'), 403);
        $companyId = (int) CompanyContext::id();
        abort_unless(WhatsAppCompanyAccess::available($companyId), 403);

        abort_unless((int) $message->company_id === $companyId, 404);
        abort_unless((int) $message->user_id === (int) $user->id, 403);

        $message->loadMissing('connection');
        $result = $media->ensureCached($message);
        if (! ($result['ok'] ?? false)) {
            return response()->json([
                'ok' => false,
                'message' => $result['error'] ?? 'Media no longer available from WhatsApp.',
            ], 422);
        }

        return response()->json([
            'ok' => true,
            'media' => [
                'ready' => true,
                'downloadable' => false,
                'url' => $result['url'] ?? null,
                'mime' => $result['mime'] ?? null,
                'filename' => $result['filename'] ?? null,
                'size' => $result['size'] ?? null,
                'cached_until' => $result['cached_until'] ?? null,
            ],
        ]);
    }

    public function transfer(Request $request, WhatsAppInboxService $inbox)
    {
        $user = $request->user();
        abort_unless($user && $user->can('whatsapp_edit'), 403);
        $companyId = (int) CompanyContext::id();
        abort_unless(WhatsAppCompanyAccess::available($companyId), 403);

        $validated = $request->validate([
            'peer_phone' => 'required|string|max:32',
            'to_user_id' => 'required|integer|min:1',
            'reason' => 'required|string|max:1000',
            'history_mode' => 'required|in:full,from_now',
            'whatsapp_connection_id' => 'nullable|integer|min:1',
            'participant_type' => 'nullable|string|max:64',
            'participant_id' => 'nullable|integer|min:1',
        ]);

        $participantType = ! empty($validated['participant_type']) && ($validated['participant_type'] !== 'unknown')
            ? (string) $validated['participant_type']
            : null;
        $participantId = $participantType && ! empty($validated['participant_id'])
            ? (int) $validated['participant_id']
            : null;

        $inbox->transfer(
            $user,
            $companyId,
            $validated['peer_phone'],
            (int) $validated['to_user_id'],
            $validated['reason'],
            $validated['history_mode'],
            isset($validated['whatsapp_connection_id']) ? (int) $validated['whatsapp_connection_id'] : null,
            $participantType,
            $participantId
        );

        return response()->json(['ok' => true]);
    }

    public function destroy(Request $request, WhatsAppInboxService $inbox)
    {
        $user = $request->user();
        abort_unless($user && $user->can('whatsapp_delete'), 403);
        $companyId = (int) CompanyContext::id();
        abort_unless(WhatsAppCompanyAccess::available($companyId), 403);

        $validated = $request->validate([
            'peer_phone' => 'required|string|max:32',
            'participant_type' => 'nullable|string|max:64',
            'participant_id' => 'nullable|integer|min:1',
        ]);

        $participantType = ! empty($validated['participant_type']) && ($validated['participant_type'] !== 'unknown')
            ? (string) $validated['participant_type']
            : null;
        $participantId = $participantType && ! empty($validated['participant_id'])
            ? (int) $validated['participant_id']
            : null;

        $deleted = $inbox->deleteUserChat(
            $user,
            $companyId,
            $validated['peer_phone'],
            $participantType,
            $participantId
        );

        return response()->json(['ok' => true, 'deleted' => $deleted]);
    }

    public function resolveConflict(Request $request, WhatsAppInboxService $inbox)
    {
        $user = $request->user();
        abort_unless($user && $user->can('whatsapp_view'), 403);
        $companyId = (int) CompanyContext::id();
        abort_unless(WhatsAppCompanyAccess::available($companyId), 403);

        $validated = $request->validate([
            'message_id' => 'required|integer|min:1',
            'participant_type' => 'required|string|max:64',
            'participant_id' => 'required|integer|min:1',
        ]);

        $inbox->resolveConflict(
            $user,
            $companyId,
            (int) $validated['message_id'],
            $validated['participant_type'],
            (int) $validated['participant_id']
        );

        return response()->json(['ok' => true]);
    }

    /**
     * Transfer targets: active users with whatsapp_view, or allotted as
     * unknown-inbound receivers for a company WhatsApp number.
     *
     * @return list<array{id:int,name:string}>
     */
    private function transferCandidates(int $companyId, int $excludeUserId): array
    {
        $unknownAssigneeIds = \App\Models\WhatsAppUnknownInboxSetting::query()
            ->where('company_id', $companyId)
            ->whereNotNull('user_id')
            ->pluck('user_id')
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => $id > 0)
            ->unique()
            ->all();
        $unknownAssigneeSet = array_fill_keys($unknownAssigneeIds, true);

        $query = User::query()
            ->with('roles')
            ->when(
                \Illuminate\Support\Facades\Schema::hasColumn('users', 'company_id'),
                fn ($q) => $q->where('company_id', $companyId)
            )
            ->where('id', '!=', $excludeUserId)
            ->orderBy('name');

        if (\Illuminate\Support\Facades\Schema::hasColumn('users', 'status')) {
            $query->where(function ($q) {
                $q->where('status', 1)
                    ->orWhere('status', true)
                    ->orWhere('status', '1');
            });
        }

        return $query
            ->limit(500)
            ->get(['id', 'name'])
            ->filter(function (User $u) use ($unknownAssigneeSet) {
                if (isset($unknownAssigneeSet[(int) $u->id])) {
                    return true;
                }

                return $u->can('whatsapp_view')
                    || (method_exists($u, 'isAdmin') && $u->isAdmin());
            })
            ->map(fn (User $u) => [
                'id' => $u->id,
                'name' => $u->name ?: ('User #'.$u->id),
            ])
            ->values()
            ->all();
    }
}
