<?php

namespace App\Http\Controllers;

use App\Models\DeleteRequest;
use App\Models\User;
use App\Models\UserNotification;
use App\Services\DeleteRequestService;
use App\Traits\GlobalPagination;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laracasts\Flash\Flash;

class DeleteRequestsController extends Controller
{
    use GlobalPagination;

    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Administrator queue — all company delete requests.
     */
    public function index(Request $request)
    {
        abort_unless(
            Auth::user()?->isAdmin() || user_can('settings_delete_requests_view'),
            403
        );

        $statusTab = $this->resolveStatusTab($request);

        $baseQueue = DeleteRequest::query()->forAdminQueue();

        $counts = [
            'pending' => (clone $baseQueue)->where('status', DeleteRequest::STATUS_PENDING)->count(),
            'approved' => (clone $baseQueue)->where('status', DeleteRequest::STATUS_APPROVED)->count(),
            'rejected' => (clone $baseQueue)->where('status', DeleteRequest::STATUS_REJECTED)->count(),
            'all' => (clone $baseQueue)
                ->where(function ($q) {
                    $q->whereNull('bin_outcome')
                        ->orWhere('bin_outcome', '!=', DeleteRequest::BIN_PERMANENTLY_DELETED);
                })
                ->count(),
        ];

        $query = DeleteRequest::with(['requester', 'reviewer'])
            ->forAdminQueue()
            ->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END")
            ->orderByDesc('created_at');

        $this->applyListFilters($query, $request, $statusTab);

        $paginationParams = $this->getPaginationParams($request, $this->getDefaultPerPage());
        $deleteRequests = $this->applyPagination($query, $paginationParams);

        $modules = DeleteRequest::query()
            ->forAdminQueue()
            ->select('module_key', 'module_name')
            ->distinct()
            ->orderBy('module_name')
            ->get();

        $requesterIds = DeleteRequest::query()
            ->forAdminQueue()
            ->whereNotNull('requested_by')
            ->distinct()
            ->pluck('requested_by')
            ->filter()
            ->all();

        $requesters = $requesterIds === []
            ? collect()
            : User::query()->whereIn('id', $requesterIds)->orderBy('name')->get(['id', 'name']);

        $pendingCount = $counts['pending'];
        $canReview = Auth::user()?->isAdmin() || user_can('settings_delete_requests_edit');
        $canOpenBin = Auth::user()?->can('settings_recycle_bin_view');
        $activeFilterCount = $this->activeFilterCount($request);

        return view('delete_requests.index', compact(
            'deleteRequests',
            'modules',
            'requesters',
            'pendingCount',
            'counts',
            'statusTab',
            'canReview',
            'canOpenBin',
            'activeFilterCount'
        ));
    }

    /**
     * Authenticated users track their own delete requests.
     */
    public function mine(Request $request)
    {
        $query = DeleteRequest::with(['reviewer'])
            ->forRequester((int) Auth::id())
            ->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $paginationParams = $this->getPaginationParams($request, $this->getDefaultPerPage());
        $deleteRequests = $this->applyPagination($query, $paginationParams);

        return view('delete_requests.mine', compact('deleteRequests'));
    }

    public function show(string $company_slug, DeleteRequest $deleteRequest)
    {
        $user = Auth::user();
        $canAdmin = $user?->isAdmin() || user_can('settings_delete_requests_view');
        $isOwner = (int) $deleteRequest->requested_by === (int) $user?->id;

        abort_unless($canAdmin || $isOwner, 403);

        // Open the record in its original module screen (with review controls for admins).
        if ($deleteRequest->isPending() && ($canAdmin || user_can('settings_delete_requests_edit'))) {
            $moduleUrl = DeleteRequestService::moduleShowUrl($deleteRequest);
            if ($moduleUrl) {
                return redirect()->to($moduleUrl);
            }
        }

        $deleteRequest->load(['requester', 'reviewer', 'restoredByUser', 'permanentlyDeletedByUser']);
        $record = DeleteRequestService::resolveDeletable($deleteRequest);

        return view('delete_requests.show', compact('deleteRequest', 'record'));
    }

    /**
     * Drawer / AJAX preview for a delete request (no module redirect).
     * Prefer embedding the same module document UI (e.g. vouchers.show_modal).
     */
    public function preview(string $company_slug, DeleteRequest $deleteRequest)
    {
        $user = Auth::user();
        $canAdmin = $user?->isAdmin() || user_can('settings_delete_requests_view');
        $isOwner = (int) $deleteRequest->requested_by === (int) $user?->id;

        abort_unless($canAdmin || $isOwner, 403);

        $deleteRequest->load(['requester', 'reviewer', 'restoredByUser', 'permanentlyDeletedByUser']);
        $record = DeleteRequestService::resolveDeletable($deleteRequest);
        $moduleUrl = DeleteRequestService::moduleShowUrl($deleteRequest);
        $resourceId = DeleteRequestService::resourceIdentifier($deleteRequest, $record);
        $canReview = $user?->isAdmin() || user_can('settings_delete_requests_edit');
        $canOpenBin = $user?->can('settings_recycle_bin_view');

        $embed = DeleteRequestService::buildPreviewEmbed($deleteRequest, $record);
        $embedModule = $embed['embed_module'];
        $embedView = $embed['embed_view'];
        $embedData = $embed['embed_data'];

        return view('delete_requests.partials.preview', compact(
            'deleteRequest',
            'record',
            'moduleUrl',
            'resourceId',
            'canReview',
            'canOpenBin',
            'embedModule',
            'embedView',
            'embedData'
        ));
    }

    public function approve(Request $request, string $company_slug, DeleteRequest $deleteRequest)
    {
        abort_unless(
            Auth::user()?->isAdmin() || user_can('settings_delete_requests_edit'),
            403
        );

        $request->validate([
            'admin_remarks' => 'nullable|string|max:2000',
        ]);

        try {
            DeleteRequestService::approve(
                $deleteRequest,
                Auth::user(),
                $request->input('admin_remarks')
            );
            $message = 'Delete request #' . $deleteRequest->id . ' approved. The record was moved to the Recycle Bin (not permanently deleted).';
            Flash::success($message)->important();
            session()->flash('success', $message);

            if ($request->boolean('stay') || $request->wantsJson()) {
                return redirect()->to($request->headers->get('referer') ?: route('settings-panel.delete-requests.index'));
            }

            return redirect()->route('settings-panel.trash.index');
        } catch (\Throwable $e) {
            Flash::error($e->getMessage());
            session()->flash('error', $e->getMessage());
        }

        return redirect()->to($request->headers->get('referer') ?: route('settings-panel.delete-requests.index'));
    }

    public function reject(Request $request, string $company_slug, DeleteRequest $deleteRequest)
    {
        abort_unless(
            Auth::user()?->isAdmin() || user_can('settings_delete_requests_edit'),
            403
        );

        $request->validate([
            'admin_remarks' => 'nullable|string|max:2000',
        ]);

        try {
            DeleteRequestService::reject(
                $deleteRequest,
                Auth::user(),
                $request->input('admin_remarks')
            );
            $message = 'Delete request #' . $deleteRequest->id . ' rejected. The record is accessible again.';
            Flash::success($message)->important();
            session()->flash('success', $message);
        } catch (\Throwable $e) {
            Flash::error($e->getMessage());
            session()->flash('error', $e->getMessage());
        }

        return redirect()->to($request->headers->get('referer') ?: route('settings-panel.delete-requests.index'));
    }

    public function bulkApprove(Request $request)
    {
        abort_unless(
            Auth::user()?->isAdmin() || user_can('settings_delete_requests_edit'),
            403
        );

        $data = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer',
            'admin_remarks' => 'nullable|string|max:2000',
        ]);

        $ok = 0;
        $errors = [];
        $requests = DeleteRequest::query()
            ->forAdminQueue()
            ->whereIn('id', $data['ids'])
            ->where('status', DeleteRequest::STATUS_PENDING)
            ->get();

        foreach ($requests as $deleteRequest) {
            try {
                DeleteRequestService::approve(
                    $deleteRequest,
                    Auth::user(),
                    $data['admin_remarks'] ?? null
                );
                $ok++;
            } catch (\Throwable $e) {
                $errors[] = '#' . $deleteRequest->id . ': ' . $e->getMessage();
            }
        }

        if ($ok > 0) {
            $message = $ok . ' request(s) approved and moved to the Recycle Bin.';
            Flash::success($message)->important();
            session()->flash('success', $message);
        }
        if ($errors !== []) {
            Flash::error(implode(' ', array_slice($errors, 0, 3)));
            session()->flash('error', implode(' ', array_slice($errors, 0, 3)));
        }

        return redirect()->route('settings-panel.delete-requests.index', array_merge(
            ['status' => 'pending'],
            $request->only(['search', 'module_key', 'requested_by', 'date_from', 'date_to', 'per_page'])
        ));
    }

    public function bulkReject(Request $request)
    {
        abort_unless(
            Auth::user()?->isAdmin() || user_can('settings_delete_requests_edit'),
            403
        );

        $data = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer',
            'admin_remarks' => 'nullable|string|max:2000',
        ]);

        $ok = 0;
        $errors = [];
        $requests = DeleteRequest::query()
            ->forAdminQueue()
            ->whereIn('id', $data['ids'])
            ->where('status', DeleteRequest::STATUS_PENDING)
            ->get();

        foreach ($requests as $deleteRequest) {
            try {
                DeleteRequestService::reject(
                    $deleteRequest,
                    Auth::user(),
                    $data['admin_remarks'] ?? null
                );
                $ok++;
            } catch (\Throwable $e) {
                $errors[] = '#' . $deleteRequest->id . ': ' . $e->getMessage();
            }
        }

        if ($ok > 0) {
            $message = $ok . ' request(s) rejected.';
            Flash::success($message)->important();
            session()->flash('success', $message);
        }
        if ($errors !== []) {
            Flash::error(implode(' ', array_slice($errors, 0, 3)));
            session()->flash('error', implode(' ', array_slice($errors, 0, 3)));
        }

        return redirect()->route('settings-panel.delete-requests.index', array_merge(
            ['status' => 'pending'],
            $request->only(['search', 'module_key', 'requested_by', 'date_from', 'date_to', 'per_page'])
        ));
    }

    /**
     * In-app notifications for the current user (admins).
     */
    public function notifications(Request $request)
    {
        $notifications = UserNotification::query()
            ->where('user_id', Auth::id())
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        return view('delete_requests.notifications', compact('notifications'));
    }

    public function markNotificationRead(string $company_slug, UserNotification $notification)
    {
        abort_unless((int) $notification->user_id === (int) Auth::id(), 403);
        $notification->markAsRead();

        $deleteRequestId = $notification->data['delete_request_id'] ?? null;
        if ($deleteRequestId) {
            return redirect()->route('settings-panel.delete-requests.show', $deleteRequestId);
        }

        return redirect()->back();
    }

    protected function resolveStatusTab(Request $request): string
    {
        if (! $request->filled('status')) {
            // Filter panel with blank status → All (demo behavior); otherwise default Pending.
            return $request->boolean('from_filter') ? 'all' : 'pending';
        }

        $status = strtolower((string) $request->input('status'));
        if (! in_array($status, ['pending', 'approved', 'rejected', 'all'], true)) {
            return 'pending';
        }

        return $status;
    }

    protected function applyListFilters($query, Request $request, string $statusTab): void
    {
        if ($statusTab === 'all') {
            $query->where(function ($q) {
                $q->whereNull('bin_outcome')
                    ->orWhere('bin_outcome', '!=', DeleteRequest::BIN_PERMANENTLY_DELETED);
            });
        } else {
            $query->where('status', $statusTab);
        }

        if ($request->filled('module_key')) {
            $query->where('module_key', $request->module_key);
        }

        if ($request->filled('requested_by')) {
            $query->where('requested_by', $request->requested_by);
        }

        if ($request->filled('request_id')) {
            $requestId = preg_replace('/\D+/', '', (string) $request->request_id);
            if ($requestId !== '') {
                $query->where('id', (int) $requestId);
            }
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->filled('search') || $request->filled('voucher')) {
            $search = trim((string) ($request->input('search') ?: $request->input('voucher')));
            $query->where(function ($q) use ($search) {
                $q->where('record_label', 'like', '%' . $search . '%')
                    ->orWhere('module_name', 'like', '%' . $search . '%')
                    ->orWhere('reason', 'like', '%' . $search . '%')
                    ->orWhere('id', $search)
                    ->orWhere('deletable_id', $search);

                if (ctype_digit((string) $search)) {
                    $childId = (int) $search;
                    $q->orWhere(function ($cascadeQ) use ($childId) {
                        $cascadeQ->whereNotNull('cascaded_records')
                            ->where(function ($jsonQ) use ($childId) {
                                $jsonQ->whereJsonContains('cascaded_records', ['id' => $childId])
                                    ->orWhere('cascaded_records', 'like', '%"id":' . $childId . '%')
                                    ->orWhere('cascaded_records', 'like', '%"id": ' . $childId . '%');
                            });
                    });
                }
            });
        }
    }

    protected function activeFilterCount(Request $request): int
    {
        $keys = ['request_id', 'voucher', 'module_key', 'requested_by', 'date_from', 'date_to'];
        $count = 0;
        foreach ($keys as $key) {
            if ($request->filled($key)) {
                $count++;
            }
        }
        // Status counts as a filter only when it is not the default pending tab and comes from the filter panel.
        if ($request->filled('status') && $request->status !== 'pending' && $request->boolean('from_filter')) {
            $count++;
        }

        return $count;
    }
}
