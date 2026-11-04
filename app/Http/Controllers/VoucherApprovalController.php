<?php

namespace App\Http\Controllers;

use App\Http\Requests\VoucherApproval\ApproveVoucherRequest;
use App\Http\Requests\VoucherApproval\OverrideVoucherApprovalRequest;
use App\Http\Requests\VoucherApproval\RejectVoucherRequest;
use App\Models\Vouchers;
use App\Services\VoucherApprovalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Laracasts\Flash\Flash;

class VoucherApprovalController extends Controller
{
    public function __construct(protected VoucherApprovalService $service)
    {
        $this->middleware('auth');
    }

    public function approve(ApproveVoucherRequest $request, string $company_slug, int $id): JsonResponse|RedirectResponse
    {
        $voucher = Vouchers::query()->findOrFail($id);
        Gate::authorize('voucher.approve', $voucher);

        $voucher = $this->service->approve($voucher, $request->user(), $request->input('remark'));

        return $this->respond($request, $voucher, 'Voucher approved successfully.');
    }

    public function reject(RejectVoucherRequest $request, string $company_slug, int $id): JsonResponse|RedirectResponse
    {
        $voucher = Vouchers::query()->findOrFail($id);
        Gate::authorize('voucher.reject', $voucher);

        $voucher = $this->service->reject($voucher, (string) $request->input('reason'), $request->user());

        return $this->respond($request, $voucher, 'Voucher rejected.');
    }

    public function resubmit(ApproveVoucherRequest $request, string $company_slug, int $id): JsonResponse|RedirectResponse
    {
        $voucher = Vouchers::query()->findOrFail($id);
        Gate::authorize('voucher.resubmit', $voucher);

        $voucher = $this->service->resubmit($voucher, $request->user(), $request->input('remark'));

        return $this->respond($request, $voucher, 'Voucher resubmitted for approval.');
    }

    public function forceApprove(OverrideVoucherApprovalRequest $request, string $company_slug, int $id): JsonResponse|RedirectResponse
    {
        $voucher = Vouchers::query()->findOrFail($id);
        Gate::authorize('voucher.forceApprove', $voucher);

        $voucher = $this->service->approve(
            $voucher,
            $request->user(),
            (string) $request->input('remark'),
            true
        );

        return $this->respond($request, $voucher, 'Voucher force-approved.');
    }

    public function reopen(OverrideVoucherApprovalRequest $request, string $company_slug, int $id): JsonResponse|RedirectResponse
    {
        $voucher = Vouchers::query()->findOrFail($id);
        Gate::authorize('voucher.reopen', $voucher);

        $voucher = $this->service->reopen($voucher, (string) $request->input('remark'), $request->user());

        return $this->respond($request, $voucher, 'Voucher reopened for approval.');
    }

    protected function respond($request, Vouchers $voucher, string $message): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'voucher' => [
                    'id' => $voucher->id,
                    'approval_status' => $voucher->approval_status,
                    'approved_by' => $voucher->approved_by,
                    'approved_at' => optional($voucher->approved_at)?->toDateTimeString(),
                    'rejected_by' => $voucher->rejected_by,
                    'rejected_at' => optional($voucher->rejected_at)?->toDateTimeString(),
                    'rejection_reason' => $voucher->rejection_reason,
                ],
            ]);
        }

        Flash::success($message);

        return redirect()->back();
    }
}
