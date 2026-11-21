<?php

namespace App\Http\Controllers;

use App\Helpers\Account;
use App\Helpers\CommonHelper;
use App\Http\Requests\CreateVouchersRequest;
use App\Http\Requests\UpdateVouchersRequest;
use App\Http\Controllers\AppBaseController;
use App\Imports\ImportVoucher;
use App\Imports\VoucherImport;
use App\Models\Accounts\Transaction;
use App\Models\Accounts\TransactionAccount;
use App\Models\Rider;
use App\Models\RiderInvoice;
use App\Models\Transactions;
use App\Models\User;
use App\Models\Banks;
use App\Models\Receipt;
use App\Models\Payment;
use App\Exports\CustomizableVoucherExport;
use App\Models\UserTableSettings;
use App\Models\Vouchers;
use App\Models\VoucherCustomField;
use App\Models\VoucherType;
use App\Services\DeleteRequestService;
use App\Services\TransactionService;
use App\Services\Voucher\VoucherImportGridService;
use App\Services\VoucherService;
use App\Support\PublicStorageDisk;
use App\Support\RiderVoucherPermissions;
use App\Support\VoucherAccountKind;
use App\Support\VoucherModulePermissions;
use App\Traits\GlobalPagination;
use App\Traits\TracksCascadingDeletions;
use Carbon\Carbon;
use DB;
use Flash;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Response;
use Yajra\DataTables\DataTables;

class VouchersController extends Controller
{
  use GlobalPagination, TracksCascadingDeletions;
  /**
   * Display a listing of the Vouchers.
   *
   * @param Request $request
   *
   * @return Response
   */
  public function index(Request $request)
  {
    if (!user_can('vouchers_view')) {
      abort(403, 'Unauthorized action.');
    }

    return $this->indexWithFilters($request);
  }

  /**
   * Handle vouchers listing with filters
   */
  private function indexWithFilters(Request $request)
  {
    // Use global pagination trait
    $paginationParams = $this->getPaginationParams($request, $this->getDefaultPerPage());

    // Landing default: Pending vouchers tab
    $approvalTab = strtolower((string) $request->input('approval_tab', 'pending'));
    if (! in_array($approvalTab, ['all', 'pending', 'approved', 'rejected', 'cashp'], true)) {
      $approvalTab = 'pending';
    }

    // Shared filters (status tab / type applied after card + tab counts as needed)
    $query = Vouchers::query()->orderBy('id', 'desc');
    $this->applyVoucherListFilters($query, $request);

    // Type ticker: Approved/Pending per voucher_type (ignore selected type + status tab)
    $typeTickerStats = $this->voucherTypeTickerStats($request);

    // Status tab counts (respect list filters including voucher_type, ignore active tab)
    $tabCounts = $this->voucherApprovalTabCounts(clone $query);

    $this->applyApprovalTabFilter($query, $approvalTab);

    // Apply pagination using the trait
    $data = $this->applyPagination($query, $paginationParams);
    VoucherAccountKind::hydrateListRows($data->items());

    $voucherModuleKey = 'vouchers';
    $voucherTypesForFilter = VoucherType::activeCodeLabelMapForModuleWithEditAccess($voucherModuleKey);
    $editDeleteFlags = VoucherType::getEditDeleteFlagsByModule($voucherModuleKey);

    // AJAX Response for filtered results
    if ($request->ajax()) {
      $tableData = view('vouchers.table', [
        'data' => $data,
        'editDeleteFlags' => $editDeleteFlags,
      ])->render();
      $paginationLinks = $data->links('components.global-pagination')->render();
      return response()->json([
        'tableData' => $tableData,
        'paginationLinks' => $paginationLinks,
        'tabCounts' => $tabCounts,
        'approval_tab' => $approvalTab,
        'typeTickerStats' => $typeTickerStats,
      ]);
    }

    return view('vouchers.index', [
      'data' => $data,
      'voucherTypesForFilter' => $voucherTypesForFilter,
      'editDeleteFlags' => $editDeleteFlags,
      'tabCounts' => $tabCounts,
      'approvalTab' => $approvalTab,
      'typeTickerStats' => $typeTickerStats,
    ]);
  }

  /**
   * Apply voucher index filters except approval_tab.
   *
   * @param  array<int, string>  $except  Filter keys to skip (e.g. voucher_type for type cards)
   */
  private function applyVoucherListFilters($query, Request $request, array $except = []): void
  {
    $skip = static fn (string $key): bool => in_array($key, $except, true);

    if (! $skip('voucher_id') && $request->filled('voucher_id')) {
      $voucherId = $request->voucher_id;
      $query->where(function ($q) use ($voucherId) {
        $q->whereRaw("CONCAT(voucher_type, '-', LPAD(id, 4, '0')) LIKE ?", ["%{$voucherId}%"])
          ->orWhere('id', 'like', "%{$voucherId}%")
          ->orWhere('voucher_type', 'like', "%{$voucherId}%");
      });
    }

    if (! $skip('trans_date') && $request->filled('trans_date')) {
      $query->whereDate('trans_date', $request->trans_date);
    }

    if (! $skip('billing_month') && $request->filled('billing_month')) {
      $billingMonth = Carbon::parse($request->billing_month);
      $query->whereYear('billing_month', $billingMonth->year)
        ->whereMonth('billing_month', $billingMonth->month);
    }

    if (! $skip('voucher_type') && $request->filled('voucher_type')) {
      $query->where('voucher_type', $request->voucher_type);
    }

    if (! $skip('created_by') && $request->filled('created_by')) {
      $query->where('Created_By', $request->created_by);
    }

    if (! $skip('account_kind') && $request->filled('account_kind')) {
      VoucherAccountKind::applyKindFilter($query, (string) $request->input('account_kind'));
    }

    if (! $skip('quick_search') && $request->filled('quick_search')) {
      $search = $request->input('quick_search');
      $query->where(function ($q) use ($search) {
        $q->whereRaw("CONCAT(voucher_type, '-', LPAD(id, 4, '0')) LIKE ?", ["%{$search}%"])
          ->orWhere('id', 'like', "%{$search}%")
          ->orWhere('voucher_type', 'like', "%{$search}%")
          ->orWhere('reference_number', 'like', "%{$search}%")
          ->orWhere('amount', 'like', "%{$search}%")
          ->orWhere('Created_By', 'like', "%{$search}%")
          ->orWhere('Updated_By', 'like', "%{$search}%");

        $matchingAccountIds = \App\Models\Accounts::query()
          ->where(function ($aq) use ($search) {
            $aq->where('name', 'like', "%{$search}%")
              ->orWhere('account_code', 'like', "%{$search}%")
              ->orWhereRaw("CONCAT(account_code, ' - ', name) LIKE ?", ["%{$search}%"]);
          })
          ->limit(200)
          ->pluck('id')
          ->all();

        if ($matchingAccountIds !== []) {
          $q->orWhereIn('payment_from', $matchingAccountIds)
            ->orWhereExists(function ($sub) use ($matchingAccountIds) {
              $sub->selectRaw('1')
                ->from('transactions')
                ->whereColumn('transactions.trans_code', 'vouchers.trans_code')
                ->where('transactions.credit', '>', 0)
                ->whereIn('transactions.account_id', $matchingAccountIds);
              if (\Illuminate\Support\Facades\Schema::hasColumn('transactions', 'deleted_at')) {
                $sub->where(function ($dq) {
                  $dq->whereNull('transactions.deleted_at');
                  if (\Illuminate\Support\Facades\Schema::hasColumn('transactions', 'approval_held_at')) {
                    $dq->orWhereNotNull('transactions.approval_held_at');
                  }
                });
              }
            });
        }
      });
    }
  }

  private function applyApprovalTabFilter($query, string $approvalTab): void
  {
    if ($approvalTab === 'pending') {
      $query->pendingApproval();
      return;
    }
    if ($approvalTab === 'approved') {
      $query->approvalApproved();
      return;
    }
    if ($approvalTab === 'rejected') {
      $query->approvalRejected();
      return;
    }
    if ($approvalTab === 'cashp') {
      $query->pendingApproval();
      VoucherAccountKind::applyKindFilter($query, VoucherAccountKind::KIND_CASH);
      return;
    }

    // Default "All": hide rejected (same as historical active list)
    $query->activeApproval();
  }

  /**
   * Approved / Pending counts per voucher_type for the type ticker cards.
   * Respects list filters except voucher_type and approval_tab (cards always show both boxes).
   *
   * @return array<string, array{a:int,p:int}>
   */
  private function voucherTypeTickerStats(Request $request): array
  {
    $query = Vouchers::query()->activeApproval();
    $this->applyVoucherListFilters($query, $request, ['voucher_type']);

    $rows = $query
      ->selectRaw("voucher_type,
        SUM(CASE WHEN approval_status = 'approved' THEN 1 ELSE 0 END) AS approved_count,
        SUM(CASE WHEN approval_status IS NULL
          OR approval_status = ''
          OR approval_status = 'pending_approval'
          OR approval_status = 'pending'
          THEN 1 ELSE 0 END) AS pending_count")
      ->groupBy('voucher_type')
      ->get();

    $stats = [];
    foreach ($rows as $row) {
      $code = (string) ($row->voucher_type ?? '');
      if ($code === '') {
        continue;
      }
      $stats[$code] = [
        'a' => (int) ($row->approved_count ?? 0),
        'p' => (int) ($row->pending_count ?? 0),
      ];
    }

    return $stats;
  }

  /**
   * @return array{all:int,pending:int,approved:int,rejected:int,cashp:int}
   */
  private function voucherApprovalTabCounts($baseQuery): array
  {
    $pending = (clone $baseQuery)->pendingApproval()->count();
    $approved = (clone $baseQuery)->approvalApproved()->count();
    $rejected = (clone $baseQuery)->approvalRejected()->count();
    $cashPending = (clone $baseQuery);
    $cashPending->pendingApproval();
    VoucherAccountKind::applyKindFilter($cashPending, VoucherAccountKind::KIND_CASH);

    return [
      'all' => (clone $baseQuery)->activeApproval()->count(),
      'pending' => $pending,
      'approved' => $approved,
      'rejected' => $rejected,
      'cashp' => $cashPending->count(),
    ];
  }

  /**
   * Export filtered vouchers using Column Control visible columns / order.
   */
  public function exportCustomizable(Request $request)
  {
    if (! user_can('vouchers_view') && ! user_can('voucher_view')) {
      abort(403, 'Unauthorized action.');
    }

    $visibleColumns = $request->input('visible_columns');
    $columnOrder = $request->input('column_order');
    $format = $request->input('format', 'excel');

    if (is_string($visibleColumns)) {
      $visibleColumns = json_decode($visibleColumns, true);
    }
    if (is_string($columnOrder)) {
      $columnOrder = json_decode($columnOrder, true);
    }

    if (empty($visibleColumns) || empty($columnOrder)) {
      $userSettings = UserTableSettings::getSettings(auth()->id(), 'vouchers_table');
      if ($userSettings) {
        $visibleColumns = $visibleColumns ?: $userSettings->visible_columns;
        $columnOrder = $columnOrder ?: $userSettings->column_order;
      }
    }

    $vouchers = $this->vouchersForExport($request);
    VoucherAccountKind::hydrateListRows($vouchers);

    $export = new CustomizableVoucherExport($visibleColumns, $columnOrder, $vouchers);

    $timestamp = now()->format('Y-m-d_H-i-s');
    $username = auth()->user()->name ?? auth()->user()->email ?? 'user';
    $username = preg_replace('/[^a-zA-Z0-9]/', '_', $username);
    $filename = "Vouchers_export_{$username}_{$timestamp}";

    return match ($format) {
      'csv' => Excel::download($export, "{$filename}.csv", \Maatwebsite\Excel\Excel::CSV),
      'pdf' => Excel::download($export, "{$filename}.pdf", \Maatwebsite\Excel\Excel::DOMPDF),
      default => Excel::download($export, "{$filename}.xlsx"),
    };
  }

  /**
   * Bulk submit delete requests for selected vouchers (same rules as single-row delete).
   */
  public function bulkDestroy(Request $request)
  {
    if (! user_can('voucher_delete') && ! user_can('vouchers_delete')) {
      abort(403, 'Unauthorized action.');
    }

    $validated = $request->validate([
      'ids' => 'required|array|min:1',
      'ids.*' => 'integer',
      'delete_reason' => 'nullable|string|max:2000',
      'reason' => 'nullable|string|max:2000',
    ]);

    $reason = trim((string) ($validated['delete_reason'] ?? $validated['reason'] ?? ''));
    if ($reason === '') {
      return response()->json([
        'errors' => ['error' => 'Please enter a delete reason.'],
      ], 422);
    }

    $request->merge(['delete_reason' => $reason, 'reason' => $reason]);

    $queued = 0;
    $skipped = 0;
    $errors = [];

    foreach ($validated['ids'] as $id) {
      $voucher = Vouchers::query()->find($id);
      if (! $voucher) {
        $skipped++;
        $errors[] = "Voucher #{$id} was not found.";
        continue;
      }

      $label = $voucher->voucher_type.'-'.str_pad((string) $voucher->id, 4, '0', STR_PAD_LEFT);

      if (function_exists('record_is_pending_deletion') && record_is_pending_deletion($voucher)) {
        $skipped++;
        $errors[] = "{$label} is already pending deletion.";
        continue;
      }

      $deleteFlags = VoucherType::getEditDeleteFlagsByModule('vouchers');
      if (empty($deleteFlags[$voucher->voucher_type]['can_delete'])) {
        $skipped++;
        $errors[] = "{$label} cannot be deleted in the Vouchers module.";
        continue;
      }

      if (VoucherType::isLockedInVouchersModule($voucher->voucher_type)) {
        $skipped++;
        $errors[] = "{$label} is locked and cannot be deleted here.";
        continue;
      }

      try {
        $result = $this->queueDeleteForVoucher($voucher, $reason);
        if (($result['ok'] ?? false) === true) {
          $queued++;
        } else {
          $skipped++;
          $errors[] = $result['message'] ?? "{$label} could not be queued.";
        }
      } catch (\Throwable $e) {
        $skipped++;
        $errors[] = "{$label}: ".$e->getMessage();
      }
    }

    $message = $queued > 0
      ? "{$queued} delete request".($queued === 1 ? '' : 's').' submitted.'
      : 'No delete requests were submitted.';
    if ($skipped > 0) {
      $message .= " {$skipped} skipped.";
    }

    return response()->json([
      'message' => $message,
      'queued' => $queued,
      'skipped' => $skipped,
      'errors' => $errors,
      'pending_deletion' => $queued > 0,
    ], $queued > 0 ? 200 : 422);
  }

  /**
   * @return array{ok:bool,message?:string}
   */
  private function queueDeleteForVoucher(Vouchers $voucher, string $reason): array
  {
    $label = $voucher->voucher_type.'-'.str_pad((string) $voucher->id, 4, '0', STR_PAD_LEFT);

    if (! DeleteRequestService::enabled() || DeleteRequestService::shouldBypassApproval()) {
      // Keep bulk path aligned with approval workflow; bypass users should delete one-by-one.
      return [
        'ok' => false,
        'message' => "{$label}: bulk delete requires the delete-request workflow.",
      ];
    }

    if (DeleteRequestService::hasPending($voucher)) {
      $existing = DeleteRequestService::lastCreatedFor($voucher);

      return [
        'ok' => false,
        'message' => DeleteRequestService::pendingMessage($existing) ?: "{$label} already has a pending delete request.",
      ];
    }

    request()->merge(['delete_reason' => $reason, 'reason' => $reason]);

    DB::beginTransaction();
    try {
      $siblings = Vouchers::where('trans_code', $voucher->trans_code)->get();
      $relatedTransactions = Transactions::where('trans_code', $voucher->trans_code)->get();

      $voucher->delete();

      $deleteRequest = request()->attributes->get('delete_approval_request')
        ?? DeleteRequestService::lastCreatedFor($voucher);

      if (! $deleteRequest) {
        DB::rollBack();

        return ['ok' => false, 'message' => "{$label}: could not create delete request."];
      }

      foreach ($siblings as $sibling) {
        if ((int) $sibling->id === (int) $voucher->id) {
          continue;
        }
        $siblingLabel = $sibling->voucher_type.'-'.str_pad((string) $sibling->id, 4, '0', STR_PAD_LEFT);
        $deleteRequest->appendCascadedRecord(Vouchers::class, $sibling->id, $siblingLabel);
      }

      foreach ($relatedTransactions as $transaction) {
        $deleteRequest->appendCascadedRecord(
          Transactions::class,
          $transaction->id,
          "Transaction #{$transaction->id} - {$transaction->narration} (Trans Code: {$transaction->trans_code})"
        );
      }

      DeleteRequestService::clearPendingIdsCache(Vouchers::class);
      DeleteRequestService::clearPendingIdsCache(Transactions::class);
      DB::commit();

      return ['ok' => true, 'message' => DeleteRequestService::pendingMessage($deleteRequest)];
    } catch (\Throwable $e) {
      DB::rollBack();

      return ['ok' => false, 'message' => "{$label}: ".$e->getMessage()];
    }
  }

  /**
   * Filtered voucher collection for export (same filters as the list, no pagination).
   *
   * @return \Illuminate\Support\Collection<int, Vouchers>
   */
  private function vouchersForExport(Request $request)
  {
    $approvalTab = strtolower((string) $request->input('approval_tab', 'all'));
    if (! in_array($approvalTab, ['all', 'pending', 'approved', 'rejected', 'cashp'], true)) {
      $approvalTab = 'all';
    }

    $query = Vouchers::query()->orderBy('id', 'desc');
    $this->applyVoucherListFilters($query, $request);
    $this->applyApprovalTabFilter($query, $approvalTab);

    return $query->get();
  }

  /**
   * Return voucher list fragment for the left sidebar (when voucher detail panel is open).
   */
  public function listSidebar(Request $request)
  {
    if (!user_can('vouchers_view')) {
      abort(403, 'Unauthorized action.');
    }

    $query = Vouchers::query()->activeApproval()->orderBy('id', 'desc');

    if ($request->filled('voucher_type')) {
      $query->where('voucher_type', $request->voucher_type);
    }
    if ($request->filled('quick_search')) {
      $search = $request->input('quick_search');
      $query->where(function ($q) use ($search) {
        $q->whereRaw("CONCAT(voucher_type, '-', LPAD(id, 4, '0')) LIKE ?", ["%{$search}%"])
          ->orWhere('id', 'like', "%{$search}%")
          ->orWhere('amount', 'like', "%{$search}%");
      });
    }

    $data = $query->paginate(20)->appends($request->query());
    $voucherTypesForCreate = VoucherType::activeCodeLabelMapForModuleWithEditAccess('vouchers');

    return view('vouchers.list_sidebar', compact('data', 'voucherTypesForCreate'));
  }

  /**
   * Show the form for creating a new Vouchers.
   * Only voucher types assigned to the vouchers module are allowed (vt query param).
   *
   * @return Response
   */
  public function create(Request $request)
  {
    $vouchers = null;
    $allowedTypes = VoucherType::activeCodeLabelMapForModuleWithEditAccess('vouchers');
    $vt = $request->query('vt');
    if ($vt !== null && !array_key_exists($vt, $allowedTypes)) {
      $vt = count($allowedTypes) > 0 ? array_key_first($allowedTypes) : null;
    }
    $voucherCustomFields = VoucherCustomField::orderBy('display_order')->get();
    return view('vouchers.create', compact('voucherCustomFields', 'vt', 'vouchers'));
  }

  /**
   * Store a newly created Vouchers in storage.
   *
   * @param CreateVouchersRequest $request
   *
   * @return Response
   */
  public function store(Request $request, $company_slug, VoucherService $voucherService)
  {
    $allowedTypes = VoucherType::activeCodeLabelMapForModule('vouchers');
    if (!array_key_exists($request->voucher_type ?? '', $allowedTypes)) {
      return response()->json(['errors' => ['voucher_type' => ['The selected voucher type is not allowed for this module.']]], 422);
    }
    try {
      $request->billing_month = $request->billing_month . "-01";
      $request->merge(['custom_field_values' => $request->input('voucher_custom_fields', [])]);



      /** @var Vouchers $vouchers */
      if ($request->voucher_type === 'TRF') {
        $result = $voucherService->FundsTransfer($request);
      } elseif ($request->voucher_type === 'JV') {
        if (!$this->debitEqualsCredit($request)) {
          return response()->json(['errors' => ['error' => 'Total debit and credit must be equal.']], 422);
        }
        $result = $voucherService->JournalVoucher($request);
      }
      /* if ($request->voucher_type == 5) {
        $result = $voucherService->InvoiceVoucher($request);
      }
      if ($request->voucher_type == 9) {
        $result = $voucherService->SimVoucher($request);
      } */
      /*  if ($request->voucher_type == 11) {
           $result = $voucherService->FuelVoucher($request);
       }
       if ($request->voucher_type == 10) {
           $result = $voucherService->RentVoucher($request);
       }
       if ($request->voucher_type == 8) {
           $result = $voucherService->RtaVoucher($request);
       } */

      if ($request->voucher_type == 'VL') {
        $result = $voucherService->loanvoucher($request);
      }
      if (in_array($request->voucher_type, ['LV', 'LE'])) {
        $result = $voucherService->DefaultVoucher($request, 'debit');
      }
      if (in_array($request->voucher_type, ['AL'])) {
        $result = $voucherService->DefaultVoucher($request, 'debit');
      }
      if (in_array($request->voucher_type, ['COD'])) {
        $result = $voucherService->DefaultVoucher($request, 'debit');
      }
      if (in_array($request->voucher_type, ['PN'], true)) {
        $result = $voucherService->DefaultVoucher($request, 'debit');
      }
      if (in_array($request->voucher_type, ['INC'], true)) {
        $result = $voucherService->DefaultVoucher($request, 'debit');
      }
      /* if (in_array($request->voucher_type, [13])) {
        $result = $voucherService->DefaultVoucher($request, 2);

      } */

      //$vouchers = Vouchers::create($input);
      return $result;
    } catch (\Illuminate\Validation\ValidationException $e) {
      return response()->json(['errors' => $e->errors()], 422);
    } catch (\Exception $e) {
      // Log the error for debugging
      \Log::error('Voucher store error: ' . $e->getMessage(), [
        'request_data' => $request->all(),
        'trace' => $e->getTraceAsString()
      ]);

      // Return user-friendly error message
      return response()->json([
        'success' => false,
        'message' => $e->getMessage()
      ], 500);
    }
  }

  /**
   * Display the specified Vouchers.
   *
   * @param int $id
   *
   * @return Response
   */
  public function show($company_slug, $id)
  {
    /** @var Vouchers $vouchers */
    $voucher = Vouchers::with(['transactions.account', 'approvedByUser', 'rejectedByUser', 'approvalEvents'])->find($id);
    $voucherModuleKey = 'vouchers';
    $editDeleteFlags = VoucherType::getEditDeleteFlagsByModule($voucherModuleKey);
    if (empty($voucher)) {
      Flash::error('Vouchers not found');

      return redirect()->back();
    }

    // Rider payment vouchers use the payment document UI (with All Vouchers sidebar).
    if ((string) $voucher->voucher_type === 'PV') {
      $payment = Payment::with(['payeeAccount', 'bank.account', 'voucher'])
        ->where('voucher_id', $voucher->id)
        ->first();
      if (! $payment && ! empty($voucher->ref_id)) {
        $payment = Payment::with(['payeeAccount', 'bank.account', 'voucher'])->find($voucher->ref_id);
      }
      if ($payment && app(\App\Services\Payment\RiderPaymentVoucherViewData::class)->isRiderPayment($payment)) {
        return app(PaymentController::class)->show(request(), $company_slug, $payment->id);
      }
    }

    $riderDoc = app(\App\Services\Voucher\RiderModuleVoucherViewData::class);
    $visaDoc = app(\App\Services\Voucher\VisaExpenseVoucherViewData::class);

    if (request()->ajax() || request()->wantsJson()) {
      return view('vouchers.show_modal', compact('voucher', 'editDeleteFlags'));
    }

    $docBuilder = $riderDoc->supports($voucher)
      ? $riderDoc
      : ($visaDoc->supports($voucher) ? $visaDoc : null);

    if ($docBuilder) {
      $doc = $docBuilder->build($voucher);

      if (request()->boolean('print') || request()->get('print')) {
        return view('vouchers.rider_voucher_print', array_merge($doc, ['doc' => $doc]));
      }

      return view('vouchers.rider_voucher_document', array_merge($doc, ['doc' => $doc]));
    }

    return view('vouchers.show', compact('voucher'));
  }

  /**
   * Show the form for editing the specified Vouchers.
   *
   * @param int $id
   *
   * @return Response
   */
  public function edit(Request $request, $company_slug, $id)
  {
    /** @var Vouchers $vouchers */
    $vouchers = Vouchers::where('trans_code', $id)->first();

    if (empty($vouchers)) {
      Flash::error('Vouchers not found');

      return redirect(route('vouchers.index'));
    }

    try {
      \App\Support\VoucherApprovalGuard::assertMayEdit($vouchers);
    } catch (\Illuminate\Validation\ValidationException $e) {
      Flash::error(collect($e->errors())->flatten()->first() ?: 'Voucher is locked.');
      return redirect()->back();
    }

    if (VoucherType::isLockedInVouchersModule($vouchers->voucher_type)) {
      abort(403, 'This voucher cannot be edited from the Vouchers module.');
    }

    $editFlags = VoucherType::getEditDeleteFlagsByModule('vouchers');
    if (empty($editFlags[$vouchers->voucher_type]['can_edit'])) {
      abort(403, 'This voucher type is not allowed to be edited in the Vouchers module.');
    }

    // Open the owning-module modal for PV / RV / EXP (same UI as Payments, Receipts, Expenses).
    $moduleActions = \App\Support\VoucherModuleActions::forVoucher($vouchers, $company_slug);
    if ($moduleActions['module'] === 'payments') {
      $paymentId = Payment::where('voucher_id', $vouchers->id)->value('id')
        ?: ($vouchers->ref_id ?: null);
      if ($paymentId) {
        return app(PaymentController::class)->edit($request, $company_slug, $paymentId);
      }
    }
    if ($moduleActions['module'] === 'receipts') {
      $receiptId = Receipt::where('voucher_id', $vouchers->id)->value('id')
        ?: ($vouchers->ref_id ?: null);
      if ($receiptId) {
        return app(ReceiptController::class)->edit($request, $company_slug, $receiptId);
      }
    }
    if ($moduleActions['module'] === 'expenses') {
      return app(ExpenseController::class)->editVoucher($company_slug, $vouchers->id);
    }
    if ($moduleActions['module'] === 'visa_expense') {
      $expenseId = $vouchers->ref_id ?: null;
      if ($expenseId) {
        return app(VisaexpenseController::class)->editLv($request, $company_slug, $expenseId);
      }
    }

    // Load full ledger lines for every other voucher type (JV, etc.), including any approval holds.
    $data = Transactions::forVoucherForm($id);

    $voucherCustomFields = VoucherCustomField::orderBy('display_order')->get();
    return view('vouchers.edit', compact('vouchers', 'data', 'voucherCustomFields'));
  }

  /**
   * Update the specified Vouchers in storage.
   *
   * @param int $id
   * @param UpdateVouchersRequest $request
   *
   * @return Response
   */
  public function update($company_slug, $id, Request $request, VoucherService $voucherService)
  {
    /** @var Vouchers $vouchers */
    $vouchers = Vouchers::find($id);

    $request->billing_month = $request->billing_month . "-01";
    $request->merge(['custom_field_values' => $request->input('voucher_custom_fields', [])]);

    /* if (array_sum($request->dr_amount) != array_sum($request->cr_amount)) {

      return response()->json(['errors' => ['error' => 'Total debit and credit must be equal.']], 422);
    } */

    if (empty($vouchers)) {
      Flash::error('Vouchers not found');

      return redirect(route('vouchers.index'));
    }

    try {
      \App\Support\VoucherApprovalGuard::assertMayEdit($vouchers);
    } catch (\Illuminate\Validation\ValidationException $e) {
      return response()->json(['errors' => $e->errors()], 422);
    }

    if (VoucherType::isLockedInVouchersModule($vouchers->voucher_type)) {
      return response()->json(['errors' => ['error' => 'This voucher cannot be edited from the Vouchers module.']], 403);
    }

    $editFlags = VoucherType::getEditDeleteFlagsByModule('vouchers');
    if (empty($editFlags[$vouchers->voucher_type]['can_edit'])) {
      return response()->json(['errors' => ['error' => 'This voucher type is not allowed to be edited in the Vouchers module.']], 403);
    }

    if ($request->voucher_type === 'TRF') {
      try {
        $voucherService->FundsTransfer($request);
      } catch (\Illuminate\Validation\ValidationException $e) {
        return response()->json(['errors' => $e->errors()], 422);
      }
    } elseif ($request->voucher_type === 'JV') {
      if (!$this->debitEqualsCredit($request)) {
        return response()->json(['errors' => ['error' => 'Total debit and credit must be equal.']], 422);
      }
      $voucherService->JournalVoucher($request);
    }
    if ($request->voucher_type === 'RFV') {
      if (!$this->debitEqualsCredit($request)) {
        return response()->json(['errors' => ['error' => 'Total debit and credit must be equal']], 422);
      }

      $riderId = $request->rider_id ?? $vouchers->rider_id;

      $riderAccountId = \App\Support\CompanyQuery::table('riders')->where('id', $riderId)->value('account_id');
      if (!$riderAccountId) {
        $riderAccountId = $request->account_id[0] ?? null;
        if (!$riderAccountId) {
          return response()->json(['errors' => ['error' => 'No account ID found for this rider']], 422);
        }
      }

      DB::beginTransaction();

      try {
        // Calculate amounts
        $totalDebit = array_sum($request->dr_amount);
        $adminCharges = 0;
        $serviceCharges = 0;

        foreach ($request->narration as $i => $note) {
          if (stripos($note, 'Admin Charges') !== false) {
            $adminCharges += floatval($request->cr_amount[$i]);
          }
          if (stripos($note, 'Service Charges') !== false) {
            $serviceCharges += floatval($request->cr_amount[$i]);
          }
        }

        $actualFineAmount = $totalDebit - ($adminCharges + $serviceCharges);

        // Update voucher
        $vouchers->rider_id = $riderAccountId;
        $vouchers->amount = $totalDebit;
        if ($request->has('reference_number')) {
          $vouchers->reference_number = $request->reference_number;
        }
        $vouchers->custom_field_values = $request->input('custom_field_values', []);
        $vouchers->save();

        // Only update rider_id in rta_fines if this is the FIRST voucher with this ref_id
        $firstVoucherId = \App\Support\CompanyQuery::table('vouchers')
          ->where('ref_id', $vouchers->ref_id)
          ->where('voucher_type', 'RFV')
          ->orderBy('id', 'asc')
          ->value('id');

        $fineUpdate = [
          'amount'       => $actualFineAmount,
          'total_amount' => $totalDebit,
          'detail'       => $request->narration[0] ?? '',
          'updated_at'   => now(),
        ];

        if ($vouchers->id == $firstVoucherId) {
          $fineUpdate['rider_id'] = $riderAccountId;
        }

        \App\Support\CompanyQuery::table('rta_fines')->where('id', $vouchers->ref_id)->update($fineUpdate);

        // Update transactions only for this voucher's trans_code
        $transactions = \App\Support\CompanyQuery::table('transactions')
          ->where('reference_id', $vouchers->ref_id)
          ->where('reference_type', 'RTA')
          ->where('trans_code', $vouchers->trans_code)
          ->orderBy('id')
          ->get();

        foreach ($transactions as $i => $txn) {
          $accountId = $request->account_id[$i] ?? 0;
          $newAccountId = ($accountId == 0 || empty($accountId)) ? $riderAccountId : $accountId;

          \App\Support\CompanyQuery::table('transactions')
            ->where('id', $txn->id)
            ->update([
              'account_id' => $newAccountId,
              'debit'      => floatval($request->dr_amount[$i]),
              'credit'     => floatval($request->cr_amount[$i]),
              'narration'  => $request->narration[$i] ?? '',
              'updated_at' => now()
            ]);
        }

        DB::commit();
        return response()->json(['message' => 'RFV voucher, RTA fine, and transactions updated successfully.']);
      } catch (\Throwable $e) {
        DB::rollBack();
        return response()->json(['errors' => ['error' => 'Update failed: ' . $e->getMessage()]], 500);
      }
    }





    if (in_array($request->voucher_type, ['LV', 'LE', 'AL', 'COD', 'PN', 'PAY', 'VC', 'INC'], true)) {
      $result = $voucherService->DefaultVoucher($request, 'debit');
    }
    /*  if (in_array($request->voucher_type, [13])) {
       $result = $voucherService->DefaultVoucher($request, 2);

     } */
    /*   if ($request->voucher_type == 11) {
          $result = $voucherService->FuelVoucher($request);
      }
      if ($request->voucher_type == 10) {
          $result = $voucherService->RentVoucher($request);
      }
      if ($request->voucher_type == 8) {
          $result = $voucherService->RtaVoucher($request);
      } */
    /*  $vouchers->fill($request->all());
     $vouchers->save();

     Flash::success('Vouchers updated successfully.'); */

    return response()->json(['message' => 'Voucher updated successfully.']);
  }

  /**
   * Remove the specified Vouchers from storage.
   *
   * @param int $id
   *
   * @throws \Exception
   *
   * @return Response
   */
  public function destroy($company_slug, $id)
  {
    /** @var Vouchers $vouchers */
    DB::beginTransaction();
    try {
      // Get all vouchers with this trans_code before deletion for cascade tracking
      $vouchers = Vouchers::where('trans_code', $id)->get();

      if ($vouchers->isEmpty()) {
        return response()->json(['errors' => ['error' => 'Voucher not found.']], 404);
      }

      // Use the first voucher for reference data
      $voucher = $vouchers->first();

      if (VoucherType::isLockedInVouchersModule($voucher->voucher_type)) {
        DB::rollBack();

        return response()->json(['errors' => ['error' => 'This voucher cannot be deleted from the Vouchers module.']], 403);
      }

      $deleteFlags = VoucherType::getEditDeleteFlagsByModule('vouchers');
      if (empty($deleteFlags[$voucher->voucher_type]['can_delete'])) {
        DB::rollBack();

        return response()->json(['errors' => ['error' => 'This voucher type is not allowed to be deleted in the Vouchers module.']], 403);
      }

      $relatedTransactions = Transactions::where('trans_code', $id)->get();

      // Delete-approval workflow: create a request, keep voucher visible until admin approves.
      if (\App\Services\DeleteRequestService::enabled()
        && ! \App\Services\DeleteRequestService::shouldBypassApproval()
      ) {
        foreach ($vouchers as $pendingCheck) {
          if (\App\Services\DeleteRequestService::hasPending($pendingCheck)) {
            DB::rollBack();
            $existing = \App\Services\DeleteRequestService::lastCreatedFor($pendingCheck);

            return response()->json([
              'message' => \App\Services\DeleteRequestService::pendingMessage($existing),
            ]);
          }
        }

        // Eloquent delete on the primary voucher fires the delete-request interceptor
        // and aborts the actual soft-delete until approval.
        $voucher->delete();

        $deleteRequest = request()->attributes->get('delete_approval_request')
          ?? \App\Services\DeleteRequestService::lastCreatedFor($voucher);

        if (! $deleteRequest) {
          DB::rollBack();

          return response()->json([
            'errors' => ['error' => 'Could not create delete request for this voucher.'],
          ], 500);
        }

        foreach ($vouchers as $sibling) {
          if ((int) $sibling->id === (int) $voucher->id) {
            continue;
          }
          $siblingLabel = $sibling->voucher_type . '-' . str_pad((string) $sibling->id, 4, '0', STR_PAD_LEFT);
          $deleteRequest->appendCascadedRecord(Vouchers::class, $sibling->id, $siblingLabel);
        }

        foreach ($relatedTransactions as $transaction) {
          $deleteRequest->appendCascadedRecord(
            Transactions::class,
            $transaction->id,
            "Transaction #{$transaction->id} - {$transaction->narration} (Trans Code: {$transaction->trans_code})"
          );
        }

        \App\Services\DeleteRequestService::clearPendingIdsCache(Vouchers::class);
        \App\Services\DeleteRequestService::clearPendingIdsCache(Transactions::class);

        DB::commit();

        return response()->json([
          'message' => \App\Services\DeleteRequestService::pendingMessage($deleteRequest),
          'delete_request_id' => $deleteRequest->id,
          'pending_deletion' => true,
        ]);
      }

      $billingMonth = $voucher->billing_month;

      // Get all accounts involved in this voucher's transactions
      $affectedAccounts = $relatedTransactions->pluck('account_id')->unique();

      // Create voucher identifier for cascade tracking
      $voucherIdentifier = $voucher->voucher_type . '-' . str_pad($voucher->id, 4, '0', STR_PAD_LEFT);

      // Soft delete and track each transaction
      foreach ($relatedTransactions as $transaction) {
        try {
          // Track cascade deletion before deleting
          $this->trackCascadeDeletion(
            Vouchers::class,
            $voucher->id,
            $voucherIdentifier,
            Transactions::class,
            $transaction->id,
            "Transaction #{$transaction->id} - {$transaction->narration} (Trans Code: {$transaction->trans_code})",
            'hasMany',
            'transactions',
            'soft',
            'Cascade deletion from Voucher deletion'
          );

          // Update deleted_by if field exists
          if (in_array('deleted_by', $transaction->getFillable())) {
            $transaction->deleted_by = auth()->id();
            $transaction->save();
          }

          // Soft delete the transaction
          $transaction->delete();
        } catch (\Exception $e) {
          \Log::error("Failed to track cascade deletion for transaction {$transaction->id}: " . $e->getMessage());
          // Continue with deletion even if tracking fails
          $transaction->delete();
        }
      }

      // Update deleted_by if field exists for all vouchers with this trans_code
      if (in_array('deleted_by', $voucher->getFillable())) {
        Vouchers::where('trans_code', $id)->update(['deleted_by' => auth()->id()]);
      }

      // Track each voucher deletion in cascade table before soft deleting
      $user = auth()->user();
      $userName = $user ? ($user->name ?? 'User #' . $user->id) : 'System';

      foreach ($vouchers as $voucherToDelete) {
        try {
          $voucherIdentifier = $voucherToDelete->voucher_type . '-' . str_pad($voucherToDelete->id, 4, '0', STR_PAD_LEFT);

          $this->trackCascadeDeletion(
            User::class,
            auth()->id(),
            $userName,
            Vouchers::class,
            $voucherToDelete->id,
            $voucherIdentifier,
            'hasMany',
            'vouchers',
            'soft',
            'User-initiated voucher deletion'
          );
          \Log::info("Voucher deletion tracked in cascade table for voucher ID: {$voucherToDelete->id}");
        } catch (\Exception $e) {
          \Log::error("Failed to track voucher deletion in cascade table for voucher ID: {$voucherToDelete->id} - " . $e->getMessage());
          // Continue with deletion even if tracking fails
        }
      }

      // Soft-delete each voucher via Eloquent so model events remain consistent.
      foreach ($vouchers as $voucherToDelete) {
        if (! $voucherToDelete->trashed()) {
          $voucherToDelete->delete();
        }
      }

      foreach ($vouchers as $voucherToDelete) {
        if (($voucherToDelete->voucher_type ?? '') === 'SV') {
          \App\Services\SalikPaymentReversalService::unpayLinkedSaliks((int) $voucherToDelete->id);
        }
      }

      // ✅ FIX: Recalculate ledger for all affected accounts
      foreach ($affectedAccounts as $accountId) {
        if ($accountId && $billingMonth) {
          $this->recalculateLedgerAfterDeletion($accountId, $billingMonth);
        }
      }

      DB::commit();
      return response()->json(['message' => delete_outcome_message('Voucher')]);
    } catch (\Exception $e) {
      DB::rollBack();
      \Log::error("Error deleting Voucher trans_code: {$id} - " . $e->getMessage());
      return response()->json(['errors' => ['error' => 'Error deleting voucher: ' . $e->getMessage()]], 500);
    }
  }

  /**
   * Compare JV/TRF debit and credit totals using money precision (2 dp).
   * Raw float array_sum can disagree even when the UI shows equal toFixed(2) totals.
   */
  private function debitEqualsCredit(Request $request): bool
  {
    $dr = round(array_sum(array_map('floatval', (array) $request->input('dr_amount', []))), 2);
    $cr = round(array_sum(array_map('floatval', (array) $request->input('cr_amount', []))), 2);

    return $dr === $cr;
  }

  /**
   * Recalculate ledger entries after deletion
   * This ensures ledger integrity without deleting all entries
   */
  private function recalculateLedgerAfterDeletion($accountId, $billingMonth)
  {
    // Delete only the ledger entry for this specific billing month
    \App\Support\CompanyQuery::table('ledger_entries')
      ->where('account_id', $accountId)
      ->where('billing_month', $billingMonth)
      ->delete();

    // Get the last ledger entry before this billing month
    $lastLedger = \App\Support\CompanyQuery::table('ledger_entries')
      ->where('account_id', $accountId)
      ->where('billing_month', '<', $billingMonth)
      ->orderBy('billing_month', 'desc')
      ->first();

    $openingBalance = $lastLedger ? $lastLedger->closing_balance : 0.00;

    // Recalculate totals for this month after deletion
    $monthTransactions = Transactions::where('account_id', $accountId)
      ->where('billing_month', $billingMonth)
      ->get();

    $debitTotal = $monthTransactions->sum('debit');
    $creditTotal = $monthTransactions->sum('credit');
    $closingBalance = $openingBalance + $debitTotal - $creditTotal;

    // Only insert a new ledger entry if there are still transactions for this month
    if ($monthTransactions->count() > 0) {
      \App\Support\CompanyQuery::insert('ledger_entries', [
        'account_id'      => $accountId,
        'billing_month'   => $billingMonth,
        'opening_balance' => $openingBalance,
        'debit_balance'   => $debitTotal,
        'credit_balance'  => $creditTotal,
        'closing_balance' => $closingBalance,
        'created_at'      => now(),
        'updated_at'      => now(),
      ]);
    }

    \Log::info("Recalculated ledger for account {$accountId} and billing month {$billingMonth}");
  }

  public static function GetInvoiceBalance()
  {
    $id = request('id');
    $type = request('type');
    $date = date('Y-m-d');
    $date = date('Y-m-d', strtotime($date . ' +1 day'));
    $invoice_balance = 0;
    $balance = 0;
    $inv_id = 0;
    if ($type == 5) {
      //Rider Invoice Balance
      $item = RiderInvoice::where('RID', $id)->first();
      if ($item) {
        $total = Transaction::where('SID', $item->id)->where('vt', 4)->sum('amount');
        $paid = Transaction::where('SID', $item->id)->where('vt', 2)->sum('amount');
        $balance = ($total) - ($paid);
        if ($balance > 0) {
          $invoice_balance += $balance;
        }
        $inv_id = $item->id;
      }
      $rider = Rider::find($id);
      $balance = Account::ob($date, $rider->account->id);
      $balance = Account::show_bal($balance);
      return ['invoice_balance' => $invoice_balance, 'inv_id' => $inv_id, 'balance' => $balance];
    }
  }

  public function fetch_invoices($company_slug, $id, $vt)
  {
    $date = date('Y-m-d');
    $date = date('Y-m-d', strtotime($date . ' +1 day'));
    if ($vt == 5) {
      $res = RiderInvoice::where('RID', $id)->whereDate('billing_month', '>=', '2024-04-01')->get();

      $htmlData = '';
      $rider_balance = 0;
      foreach ($res as $item) {
        /* $total = Transaction::where('SID', $item->id)->where('vt', 4)->sum('amount');
        $paid = Transaction::where('SID', $item->id)->where('vt', 2)->sum('amount');
        $balance = ($total) - ($paid); */
        $balance = Account::InvoiceBalance($item->id);
        if ($balance > 0) {
          $trans_acc_id = TransactionAccount::where(['PID' => 21, 'Parent_Type' => $item->RID])->value('id');
          $rider_balance = Account::Monthly_ob($date, $trans_acc_id);
          $htmlData .= '
                <div class="row">
                <input type="hidden" name="inv_id[]" value="' . $item->id . '">
                <input type="hidden" name="id[]" value="' . $item->rider->id . '">
                <input type="hidden" name="inv_billing_month[]" value="' . $item->billing_month . '">

                        <div class="form-group col-md-7">
                            <label>Narration</label>
                            <textarea name="narration[]" class="form-control form-control-sm narration" rows="10" placeholder="Narration" style="height: 40px !important;">Payment to Rider against Invoice #' . $item->id . ' - Billing Month: ' . $item->billing_month . '</textarea>
                        </div>
                        <div class="form-group col-md-2">
                            <label>Invoice Balance</label>
                            <input type="number" name="" class="form-control form-control-sm dr_amount" value="' . $balance . '" readonly placeholder="Balance Amount">
                        </div>
                        <div class="form-group col-md-2">
                            <label>Amount</label>
                            <input type="number" name="amount[]" step="any" class="form-control form-control-sm cr_amount" onkeyup="getTotal();" placeholder="Paid Amount">
                        </div>
                    </div>
                    <!--row-->
            ';
        }
      }
      //SELECT SUM(t.amount) FROM rider_invoices rv INNER JOIN transactions AS t ON rv.id=t.SID WHERE vt='4' and rv.VID=1
      return compact('htmlData', 'rider_balance');
    } else {
      $res = RiderInvoice::where('VID', $id)->get();
      $htmlData = '';
      $vendor_balance = 0;
      foreach ($res as $item) {
        /* $total = Transaction::where('SID', $item->id)->where('vt', 4)->sum('amount');
        $paid = Transaction::where('SID', $item->id)->where('vt', 2)->sum('amount');
        $balance = ($total) - ($paid); */
        $balance = Account::InvoiceBalance($item->id);
        if ($balance > 0) {
          $trans_acc_id = TransactionAccount::where(['PID' => 21, 'Parent_Type' => $item->RID])->value('id');
          $rider_balance = Account::Monthly_ob($date, $trans_acc_id);
          $htmlData .= '
                <tr><td>
                <div class="row">
                <input type="hidden" name="inv_id[]" value="' . $item->id . '">
                <input type="hidden" name="inv_billing_month[]" value="' . $item->billing_month . '">
                        <div class="form-group col-md-2">
                            <label for="exampleInputEmail1">Payment To</label>
                            <input type="hidden" name="id[]" value="' . $item->rider->id . '">
                               ' . $item->rider->name . '(' . $item->rider->rider_id . ')

                        </div>
                        <div class="form-group col-md-4">
                            <label>Narration</label>
                            <textarea name="narration[]" class="form-control form-control-sm narration" rows="10" placeholder="Narration" style="height: 40px !important;">Payment to Rider against #' . $item->id . ' through vendor</textarea>
                        </div>
                        <div class="form-group col-md-2">
                            <label>Rider Balance</label>
                            <input type="text" name="" class="form-control form-control-sm" value="' . Account::show_bal($rider_balance) . '" readonly placeholder="Balance Amount">
                        </div>
                        <div class="form-group col-md-2">
                            <label>Invoice Balance</label>
                            <input type="number" step="any" name="" class="form-control form-control-sm" value="' . $balance . '" readonly placeholder="Balance Amount">
                        </div>
                        <div class="form-group col-md-2">
                            <label>Amount</label>
                            <input type="number" step="any" name="amount[]" class="form-control form-control-sm amount" step="any" onkeyup="getTotal();" placeholder="Paid Amount">
                        </div>
                    </div>
                    </td>
                    <td width="100"><input type="button" class="ibtnDel btn btn-md btn-xs btn-danger " style="margin-top:22px;"  value="Delete"></td>
                    </tr>
                    <!--row-->
            ';
        }
        $vendor_balance += $rider_balance;
      }
      //SELECT SUM(t.amount) FROM rider_invoices rv INNER JOIN transactions AS t ON rv.id=t.SID WHERE vt='4' and rv.VID=1
      $vendor_balance = Account::show_bal($vendor_balance);
      return compact('htmlData', 'vendor_balance');
    }
  }

  public function fileUpload(Request $request, $company_slug, $id)
  {
    $voucher = Vouchers::find($id);

    if (! $voucher) {
      return response()->json(['message' => 'Voucher not found'], 404);
    }

    $riderVoucherCode = RiderVoucherPermissions::codeFromVoucherType($voucher->voucher_type);
    if ($riderVoucherCode !== null) {
      if (! RiderVoucherPermissions::canUpload($riderVoucherCode)) {
        return response()->json(['message' => 'Unauthorized action.'], 403);
      }
    } else {
      $canUpload = VoucherModulePermissions::canUpload()
        || user_can('cash_&_banks_payments_edit')
        || user_can('cash_&_banks_payments_create')
        || user_can('employees_payments_edit')
        || user_can('employees_payments_create')
        || user_can('customers_payments_edit')
        || user_can('customers_payments_create')
        || user_can('sims_payments_edit')
        || user_can('sims_payments_create')
        || user_can('leasing_companies_payments_edit')
        || user_can('leasing_companies_payments_create')
        || user_can('suppliers_payments_edit')
        || user_can('suppliers_payments_create');

      if (! $canUpload) {
        return response()->json(['message' => 'Unauthorized action.'], 403);
      }
    }

    if ($request->isMethod('POST')) {
      if (! $request->hasFile('attach_file')) {
        return response()->json(['message' => 'Please select a file to upload'], 422);
      }

      $request->validate([
        'attach_file' => 'required|file|max:10240',
      ]);

      try {
        $photo = $request->file('attach_file');
        $safeBase = preg_replace('/[^A-Za-z0-9._-]/', '_', $photo->getClientOriginalName()) ?: 'document';
        $fileName = time() . '_' . $safeBase;

        $oldFile = $voucher->attach_file ? basename((string) $voucher->attach_file) : null;

        // storeAs returns "vouchers/{fileName}"; views expect the basename only.
        PublicStorageDisk::storeUploadedFile($photo, 'vouchers', $fileName);

        if ($oldFile && $oldFile !== $fileName) {
          PublicStorageDisk::delete('vouchers/' . $oldFile);
        }

        $voucher->attach_file = $fileName;
        $voucher->updated_by = auth()->id();
        $voucher->save();

        // Keep linked payment attachment in sync (PV and similar).
        $payment = Payment::where('voucher_id', $voucher->id)->first();
        if ($payment) {
          $payment->attachment = $fileName;
          $payment->updated_by = auth()->id();
          $payment->save();
        }
      } catch (\Throwable $e) {
        report($e);

        return response()->json([
          'message' => 'File upload failed. Please try again or contact support if this persists.',
        ], 500);
      }

      return response()->json(['message' => 'File uploaded successfully', 'reload' => true], 200);
    }

    return view('vouchers.attach_file', [
      'id' => $id,
      'voucher' => $voucher,
      'company_slug' => $company_slug,
    ]);
  }


  public function import(Request $request)
  {
    if ($request->isMethod('post')) {
      $rules = [
        'file' => 'required|max:50000|mimes:xlsx,csv'
      ];
      $message = [
        'file.required' => 'Excel File Required'
      ];

      $this->validate($request, $rules, $message);
      Excel::import(new ImportVoucher(), $request->file('file'));
    }

    return view('vouchers.import');
  }

  /**
   * Parse Excel/CSV into an editable import grid (no DB writes).
   */
  public function importPreview(Request $request, VoucherImportGridService $service)
  {
    if (! user_can('vouchers_create') && ! user_can('voucher_create')) {
      abort(403, 'Unauthorized action.');
    }

    $request->validate([
      'file' => 'required|file|mimes:xlsx,xls,csv,txt|max:51200',
    ]);

    try {
      $result = $service->preview($request->file('file'));
      $filename = $request->file('file')->getClientOriginalName();
      $paymentFrom = $request->input('payment_from');
      $this->storeVoucherImportDraft([
        'filename' => $filename,
        'payment_from' => $paymentFrom !== null && $paymentFrom !== '' ? (int) $paymentFrom : null,
        'rows' => $result['rows'],
        'counts' => $result['counts'],
      ]);

      return response()->json([
        'success' => true,
        'rows' => $result['rows'],
        'counts' => $result['counts'],
        'draft' => $this->voucherImportDraftPayload(),
      ]);
    } catch (\Throwable $e) {
      \Log::error('Voucher import preview failed: '.$e->getMessage());

      return response()->json([
        'success' => false,
        'message' => 'Could not read this file',
      ], 422);
    }
  }

  /**
   * Re-validate edited grid rows.
   */
  public function importValidate(Request $request, VoucherImportGridService $service)
  {
    if (! user_can('vouchers_create') && ! user_can('voucher_create')) {
      abort(403, 'Unauthorized action.');
    }

    $rows = $request->input('rows', []);
    if (! is_array($rows)) {
      return response()->json(['success' => false, 'message' => 'Invalid rows'], 422);
    }

    $result = $service->validateRows($rows);
    $existing = $this->voucherImportDraft() ?? [];
    $paymentFrom = $request->input('payment_from', $existing['payment_from'] ?? null);
    $this->storeVoucherImportDraft([
      'filename' => $request->input('filename', $existing['filename'] ?? null),
      'payment_from' => $paymentFrom !== null && $paymentFrom !== '' ? (int) $paymentFrom : null,
      'rows' => $result['rows'],
      'counts' => $result['counts'],
    ]);

    return response()->json([
      'success' => true,
      'rows' => $result['rows'],
      'counts' => $result['counts'],
      'draft' => $this->voucherImportDraftPayload(),
    ]);
  }

  /**
   * Save / refresh the in-progress import draft (survives page reload).
   */
  public function importDraftSave(Request $request, VoucherImportGridService $service)
  {
    if (! user_can('vouchers_create') && ! user_can('voucher_create')) {
      abort(403, 'Unauthorized action.');
    }

    $rows = $request->input('rows', []);
    if (! is_array($rows) || $rows === []) {
      return response()->json(['success' => false, 'message' => 'No rows to save'], 422);
    }

    $result = $service->validateRows($rows);
    $existing = $this->voucherImportDraft() ?? [];
    $paymentFrom = $request->input('payment_from', $existing['payment_from'] ?? null);
    $this->storeVoucherImportDraft([
      'filename' => $request->input('filename', $existing['filename'] ?? null),
      'payment_from' => $paymentFrom !== null && $paymentFrom !== '' ? (int) $paymentFrom : null,
      'rows' => $result['rows'],
      'counts' => $result['counts'],
    ]);

    return response()->json([
      'success' => true,
      'draft' => $this->voucherImportDraftPayload(),
      'rows' => $result['rows'],
      'counts' => $result['counts'],
    ]);
  }

  /**
   * Return the current import draft from session (if any).
   */
  public function importDraftShow()
  {
    if (! user_can('vouchers_create') && ! user_can('voucher_create')) {
      abort(403, 'Unauthorized action.');
    }

    $draft = $this->voucherImportDraftPayload();

    return response()->json([
      'success' => true,
      'draft' => $draft,
      'has_draft' => $draft !== null,
    ]);
  }

  /**
   * Discard the in-progress import draft.
   */
  public function importDraftDestroy()
  {
    if (! user_can('vouchers_create') && ! user_can('voucher_create')) {
      abort(403, 'Unauthorized action.');
    }

    $this->clearVoucherImportDraft();

    return response()->json([
      'success' => true,
      'message' => 'Import draft cleared',
    ]);
  }

  /**
   * Credit-account balance for the import modal (demo-style warning line).
   */
  public function importAccountBalance(Request $request)
  {
    if (! user_can('vouchers_create') && ! user_can('voucher_create')) {
      abort(403, 'Unauthorized action.');
    }

    $accountId = (int) $request->query('account_id', 0);
    if ($accountId < 1) {
      return response()->json(['success' => false, 'message' => 'Account required'], 422);
    }

    $row = \App\Models\Transactions::where('account_id', $accountId)
      ->selectRaw('COALESCE(SUM(debit),0) as total_debit, COALESCE(SUM(credit),0) as total_credit')
      ->first();

    $balance = (float) ($row->total_debit ?? 0) - (float) ($row->total_credit ?? 0);

    return response()->json([
      'success' => true,
      'account_id' => $accountId,
      'balance' => round($balance, 2),
      'formatted' => number_format($balance, 2),
    ]);
  }

  /**
   * Commit edited import grid rows (creates JV vouchers like legacy import).
   */
  public function importCommit(Request $request, VoucherImportGridService $service)
  {
    if (! user_can('vouchers_create') && ! user_can('voucher_create')) {
      abort(403, 'Unauthorized action.');
    }

    $validated = $request->validate([
      'payment_from' => 'required|integer',
      'rows' => 'required|array|min:1',
    ]);

    $result = $service->commit((int) $validated['payment_from'], $validated['rows']);
    $msg = 'Import finished: '.$result['created'].' voucher'.($result['created'] === 1 ? '' : 's').' created';
    if ($result['skipped'] > 0) {
      $msg .= ', '.$result['skipped'].' skipped';
    }

    if ($result['created'] > 0 && $result['skipped'] === 0) {
      $this->clearVoucherImportDraft();
    } else {
      // Keep draft so a page reload can resume the same sheet.
      // After a partial import, keep only rows that were skipped / still have errors.
      $annotated = $service->validateRows($validated['rows']);
      $keepRows = $annotated['rows'];
      if ($result['created'] > 0) {
        $errorLines = [];
        foreach ($result['errors'] as $errLine) {
          if (preg_match('/^Row\s+(\d+):/', (string) $errLine, $m)) {
            $errorLines[(int) $m[1]] = true;
          }
        }
        $keepRows = [];
        foreach ($annotated['rows'] as $i => $row) {
          $line = $i + 1;
          if (! empty($row['errors']) || isset($errorLines[$line])) {
            $keepRows[] = $row;
          }
        }
      }

      if ($keepRows === []) {
        $this->clearVoucherImportDraft();
      } else {
        $re = $service->validateRows($keepRows);
        $existing = $this->voucherImportDraft() ?? [];
        $this->storeVoucherImportDraft([
          'filename' => $request->input('filename', $existing['filename'] ?? null),
          'payment_from' => (int) $validated['payment_from'],
          'rows' => $re['rows'],
          'counts' => $re['counts'],
        ]);
      }
    }

    return response()->json([
      'success' => $result['created'] > 0,
      'message' => $msg,
      'created' => $result['created'],
      'skipped' => $result['skipped'],
      'errors' => $result['errors'],
      'draft' => $this->voucherImportDraftPayload(),
    ], $result['created'] > 0 ? 200 : 422);
  }

  private function voucherImportDraftSessionKey(): string
  {
    $userId = (int) (auth()->id() ?? 0);
    $company = (string) (request()->route('company_slug') ?? session('company_slug') ?? 'default');

    return 'voucher_import_draft.'.$company.'.'.$userId;
  }

  /**
   * @return array<string, mixed>|null
   */
  private function voucherImportDraft(): ?array
  {
    $draft = session($this->voucherImportDraftSessionKey());

    return is_array($draft) && ! empty($draft['rows']) ? $draft : null;
  }

  /**
   * @return array<string, mixed>|null
   */
  private function voucherImportDraftPayload(): ?array
  {
    $draft = $this->voucherImportDraft();
    if (! $draft) {
      return null;
    }

    return [
      'filename' => $draft['filename'] ?? null,
      'payment_from' => $draft['payment_from'] ?? null,
      'rows' => $draft['rows'] ?? [],
      'counts' => $draft['counts'] ?? ['ok' => 0, 'err' => 0, 'warn' => 0],
      'saved_at' => $draft['saved_at'] ?? null,
    ];
  }

  /**
   * @param  array{filename?:mixed,payment_from?:mixed,rows:list<array<string,mixed>>,counts?:array<string,int>}  $data
   */
  private function storeVoucherImportDraft(array $data): void
  {
    session([
      $this->voucherImportDraftSessionKey() => [
        'filename' => isset($data['filename']) ? (string) $data['filename'] : null,
        'payment_from' => $data['payment_from'] ?? null,
        'rows' => array_values($data['rows'] ?? []),
        'counts' => $data['counts'] ?? ['ok' => 0, 'err' => 0, 'warn' => 0],
        'saved_at' => now()->toIso8601String(),
      ],
    ]);
  }

  private function clearVoucherImportDraft(): void
  {
    session()->forget($this->voucherImportDraftSessionKey());
  }

  public function cloneVoucher($company_slug, $id)
  {
    /** @var Vouchers $vouchers */
    $vouchers = Vouchers::where('trans_code', $id)->first();
    $vouchers->trans_code = null;
    $vouchers->billing_month = Carbon::parse($vouchers->billing_month)->format('Y-m');
    if ($vouchers->voucher_type == 'RV') {
      $receipt = Receipt::find($vouchers->ref_id);
      if (empty($receipt)) {
        Flash::error('Receipt not found');
        return redirect()->back();
      }

      $banks = Banks::active()->get();
      $receipt->billing_month = \Carbon\Carbon::parse($receipt->billing_month)->format('Y-m');
      return view('receipts.create', compact('receipt', 'banks'));
    } elseif ($vouchers->voucher_type == 'PV') {
      $payment = Payment::find($vouchers->ref_id);
      if (empty($payment)) {
        Flash::error('Payment not found');
        return redirect()->back();
      }

      $banks = Banks::active()->get();
      $payment->billing_month = \Carbon\Carbon::parse($payment->billing_month)->format('Y-m');
      $payment->amount = $payment->amount - $payment->bank_charges;

      return view('payments.create', compact('payment', 'banks'));
    } elseif (in_array($vouchers->voucher_type, ['JV', 'TRF', 'RFV', 'AL', 'COD', 'PN', 'IL', 'PAY', 'VC', 'INC'], true)) {
      $data = Transactions::forVoucherForm($id);
    } else {
      $data = Transactions::forVoucherForm($id)->where('debit', '>', 0)->values();
    }

    if (empty($vouchers)) {
      Flash::error('Vouchers not found');

      return redirect(route('vouchers.index'));
    }

    $voucherCustomFields = VoucherCustomField::orderBy('display_order')->get();
    return view('vouchers.create', compact('vouchers', 'data', 'voucherCustomFields'));
  }
}
