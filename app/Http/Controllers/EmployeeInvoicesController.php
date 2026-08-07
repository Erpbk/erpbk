<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateEmployeeInvoicesRequest;
use App\Http\Requests\UpdateEmployeeInvoicesRequest;
use App\Imports\ImportEmployeeInvoice;
use App\Models\Employee;
use App\Models\EmployeeInvoices;
use App\Models\Items;
use App\Models\Payment;
use App\Models\Transactions;
use App\Repositories\EmployeeInvoicesRepository;
use App\Services\EmployeeInvoice\EmployeeInvoiceViewDataBuilder;
use App\Traits\GlobalPagination;
use Carbon\Carbon;
use Flash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class EmployeeInvoicesController extends AppBaseController
{
    use GlobalPagination;

    private $employeeInvoicesRepository;

    public function __construct(EmployeeInvoicesRepository $employeeInvoicesRepo)
    {
        $this->employeeInvoicesRepository = $employeeInvoicesRepo;
        $this->middleware('permission:employees_invoice_view')->only('index', 'show');
        $this->middleware('permission:employees_invoice_create')->only('create', 'store', 'importForm', 'import');
        $this->middleware('permission:employees_invoice_edit')->only('edit', 'update');
        $this->middleware('permission:employees_invoice_delete')->only('destroy', 'bulkDelete');
    }

    public function index(Request $request)
    {
        $paginationParams = $this->getPaginationParams($request, $this->getDefaultPerPage());
        $query = EmployeeInvoices::query()
            ->with(['employee', 'items'])
            ->orderBy('billing_month', 'desc');

        if ($request->filled('id')) {
            $query->where('id', 'like', '%'.$request->id.'%');
        }
        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }
        if ($request->filled('billing_month')) {
            $billingMonth = Carbon::parse($request->billing_month);
            $query->whereYear('billing_month', $billingMonth->year)
                ->whereMonth('billing_month', $billingMonth->month);
        }
        if ($request->filled('zone')) {
            $query->where('zone', $request->zone);
        }
        if ($request->filled('performance')) {
            $query->where('performance', $request->performance);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $data = $this->applyPagination($query, $paginationParams);

        $billingMonth = $request->filled('billing_month')
            ? Carbon::parse($request->billing_month)
            : now();

        $currentMonthTotal = EmployeeInvoices::whereYear('billing_month', $billingMonth->year)
            ->whereMonth('billing_month', $billingMonth->month)
            ->sum('total_amount');

        if ($request->ajax()) {
            $tableData = view('employee_invoices.table', compact('data', 'currentMonthTotal'))->render();
            $paginationLinks = $data->links('components.global-pagination')->render();

            return response()->json([
                'tableData' => $tableData,
                'paginationLinks' => $paginationLinks,
                'currentMonthTotal' => number_format($currentMonthTotal, 1),
            ]);
        }

        return view('employee_invoices.index', compact('data', 'currentMonthTotal'));
    }

    public function create()
    {
        $employees = Employee::dropdown();
        $items = Items::dropdown('employee');

        return view('employee_invoices.create', compact('employees', 'items'));
    }

    public function store(CreateEmployeeInvoicesRequest $request)
    {
        DB::beginTransaction();
        try {
            $this->employeeInvoicesRepository->record($request);
            DB::commit();
            if ($request->ajax()) {
                return response()->json(['success' => true, 'message' => 'Employee invoice saved successfully.']);
            }
            Flash::success('Employee invoice saved successfully.');

            return redirect()->back();
        } catch (\Exception $e) {
            DB::rollback();
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
            }
            Flash::error($e->getMessage());

            return redirect()->back()->withInput();
        }
    }

    public function show($comapny_slug, $id)
    {
        $employeeInvoice = $this->employeeInvoicesRepository->find($id);
        if (empty($employeeInvoice)) {
            session()->flash('error', 'Employee invoice not found');

            return redirect(route('employeeInvoices.index'));
        }

        $employeeInvoice->load([
            'items',
            'employee' => function ($query) {
                $query->withTrashed()->with(['department', 'account']);
            },
        ]);

        if (! $employeeInvoice->employee) {
            session()->flash('error', 'Employee record not found for this invoice');

            return redirect(route('employeeInvoices.index'));
        }

        $viewData = array_merge(
            app(EmployeeInvoiceViewDataBuilder::class)->build($employeeInvoice),
            ['employeeInvoice' => $employeeInvoice]
        );

        return response(view('employee_invoices.show', $viewData)->render());
    }

    public function edit($comapny_slug, $id)
    {
        $invoice = $this->employeeInvoicesRepository->find($id);
        if (empty($invoice)) {
            session()->flash('error', 'Employee invoice not found');

            return redirect(route('employeeInvoices.index'));
        }

        $employees = Employee::dropdown();
        $items = Items::dropdown('employee');

        return view('employee_invoices.edit', compact('invoice', 'employees', 'items'));
    }

    public function update($comapny_slug, $id, UpdateEmployeeInvoicesRequest $request)
    {
        try {
            $invoice = $this->employeeInvoicesRepository->find($id);
            if (empty($invoice)) {
                session()->flash('error', 'Employee invoice not found');

                return redirect(route('employeeInvoices.index'));
            }

            $this->employeeInvoicesRepository->record($request, $id);
            session()->flash('success', 'Employee invoice updated successfully.');

            return redirect(route('employeeInvoices.index'));
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());

            return redirect()->back()->withInput();
        }
    }

    public function destroy($comapny_slug, $id)
    {
        $invoice = $this->employeeInvoicesRepository->find($id);
        if (empty($invoice)) {
            session()->flash('error', 'Employee invoice not found');

            return redirect(route('employeeInvoices.index'));
        }

        $payment = Payment::where('payee_account_id', $invoice->employee->account_id)
            ->where('reference', 'like', '%'.$invoice->invoice_number.'%')
            ->exists();

        if ($payment) {
            return response()->json([
                'message' => 'Cannot Delete Invoice. Payment has already been Made',
            ], 500);
        }

        Transactions::where('reference_type', 'EmployeeInvoice')->where('reference_id', $id)->delete();
        $invoice->deleted_by = Auth::id();
        $invoice->save();
        $invoice->delete();

        return response()->json([
            'message' => 'Employee invoice deleted successfully.',
            'reload' => true,
        ]);
    }

    public function bulkDelete(Request $request)
    {
        $invoiceIds = $request->input('invoice_ids', []);
        if (empty($invoiceIds)) {
            return response()->json(['success' => false, 'message' => 'No invoices selected.'], 400);
        }

        $deleted = 0;
        foreach ($invoiceIds as $invoiceId) {
            $invoice = $this->employeeInvoicesRepository->find($invoiceId);
            if (! $invoice || $invoice->status == 1) {
                continue;
            }
            Transactions::where('reference_type', 'EmployeeInvoice')->where('reference_id', $invoiceId)->delete();
            $invoice->deleted_by = Auth::id();
            $invoice->save();
            $invoice->delete();
            $deleted++;
        }

        return response()->json([
            'success' => true,
            'message' => $deleted.' employee invoice(s) deleted successfully.',
        ]);
    }

    public function importForm()
    {
        $items = Items::dropdown('employee')->map(function ($item) {
            return [
                'id' => $item->id,
                'name' => $item->name,
                'price' => (float) ($item->price ?? 0),
                'vat' => (float) ($item->vat ?? 0),
            ];
        })->values();

        return view('employee_invoices.import', compact('items'));
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,csv,xls',
            'col_employee_id' => 'required|integer|min:1',
            'col_inv_date' => 'required|integer|min:1',
            'col_billing_month' => 'required|integer|min:1',
            'col_descriptions' => 'required|integer|min:1',
            'col_notes' => 'nullable|integer|min:1',
            'item_map' => 'required|array|min:1',
            'item_map.*.item_id' => 'required|integer|exists:items,id',
            'item_map.*.col' => 'required|integer|min:1',
            'item_map.*.rate' => 'required|numeric',
            'item_map.*.vat' => 'required|numeric|min:0',
            'item_map.*.discount' => 'required|numeric|min:0',
        ]);

        $columnMap = [
            'employee_id' => (int) $request->col_employee_id,
            'inv_date' => (int) $request->col_inv_date,
            'billing_month' => (int) $request->col_billing_month,
            'descriptions' => (int) $request->col_descriptions,
            'notes' => $request->filled('col_notes') ? (int) $request->col_notes : null,
        ];

        $itemDefs = [];
        $itemIds = [];
        $itemCols = [];

        foreach ($request->input('item_map', []) as $row) {
            $itemId = (int) $row['item_id'];
            $col = (int) $row['col'];

            if (in_array($itemId, $itemIds, true)) {
                $message = 'Each item can only be mapped once.';
                if ($request->ajax()) {
                    return response()->json(['success' => false, 'message' => $message], 422);
                }
                Flash::error($message);

                return redirect()->back();
            }

            if (in_array($col, $itemCols, true)) {
                $message = 'Item quantity columns must be unique.';
                if ($request->ajax()) {
                    return response()->json(['success' => false, 'message' => $message], 422);
                }
                Flash::error($message);

                return redirect()->back();
            }

            $itemIds[] = $itemId;
            $itemCols[] = $col;
            $itemDefs[] = [
                'item_id' => $itemId,
                'col' => $col,
                'rate' => (float) $row['rate'],
                'vat' => (float) $row['vat'],
                'discount' => (float) $row['discount'],
            ];
        }

        $employeeItems = Items::whereIn('id', $itemIds)
            ->where('status', 1)
            ->whereJsonContains('owner', 'employee')
            ->pluck('id')
            ->all();

        if (count($employeeItems) !== count($itemIds)) {
            $message = 'One or more mapped items are invalid or not assigned to the employee module.';
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => $message], 422);
            }
            Flash::error($message);

            return redirect()->back();
        }

        $headerCols = array_filter($columnMap, fn ($v) => $v !== null);
        $allCols = array_merge(array_values($headerCols), $itemCols);
        if (count($allCols) !== count(array_unique($allCols))) {
            $message = 'Column numbers must be unique across header and item mappings.';
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => $message], 422);
            }
            Flash::error($message);

            return redirect()->back();
        }

        try {
            $import = new ImportEmployeeInvoice($columnMap, $itemDefs);
            Excel::import($import, $request->file('file'));
            $importedCount = $import->importedCount;
            $skippedLog = $import->skippedLog;
            $skippedCount = count($skippedLog);

            $message = "Import finished. Imported: {$importedCount}.";
            if ($skippedCount > 0) {
                $message .= " Skipped: {$skippedCount}.";
            }

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'imported_count' => $importedCount,
                    'skipped_count' => $skippedCount,
                    'skipped_log' => array_values($skippedLog),
                ]);
            }
            Flash::success($message);
            if ($skippedCount > 0) {
                session()->flash('import_skipped_log', $skippedLog);
            }

            return redirect()->route('employeeInvoices.index');
        } catch (\Exception $e) {
            \Log::error('Employee invoice import failed: '.$e->getMessage());
            $message = 'Import failed: '.$e->getMessage();
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $message,
                ], 422);
            }
            Flash::error($message);

            return redirect()->back();
        }
    }
}
