<?php

namespace App\Http\Controllers;

use App\Models\DeleteRequest;
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

        $query = DeleteRequest::with(['requester', 'reviewer'])
            // Only primary delete requests (never cascade/child leftovers).
            ->whereIn('module_key', array_keys(config('delete_approval.modules', [])))
            ->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END")
            ->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('module_key')) {
            $query->where('module_key', $request->module_key);
        }

        if ($request->filled('requested_by')) {
            $query->where('requested_by', $request->requested_by);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('record_label', 'like', '%' . $search . '%')
                    ->orWhere('module_name', 'like', '%' . $search . '%')
                    ->orWhere('reason', 'like', '%' . $search . '%')
                    ->orWhere('id', $search);
            });
        }

        $paginationParams = $this->getPaginationParams($request, $this->getDefaultPerPage());
        $deleteRequests = $this->applyPagination($query, $paginationParams);

        $modules = DeleteRequest::query()
            ->select('module_key', 'module_name')
            ->distinct()
            ->orderBy('module_name')
            ->get();

        $pendingCount = DeleteRequest::pending()->count();

        return view('delete_requests.index', compact(
            'deleteRequests',
            'modules',
            'pendingCount'
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
            Flash::success('Delete request #' . $deleteRequest->id . ' approved. The record was moved to the Recycle Bin (not permanently deleted).')->important();
            return redirect()->route('settings-panel.trash.index');
        } catch (\Throwable $e) {
            Flash::error($e->getMessage());
        }

        return redirect()->route('settings-panel.delete-requests.index');
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
            Flash::success('Delete request #' . $deleteRequest->id . ' rejected. The record is accessible again.')->important();
        } catch (\Throwable $e) {
            Flash::error($e->getMessage());
        }

        return redirect()->route('settings-panel.delete-requests.index');
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
}
