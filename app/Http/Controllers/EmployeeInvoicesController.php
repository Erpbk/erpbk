<?php

namespace App\Http\Controllers;

use App\Http\Controllers\AppBaseController;
use App\Http\Requests\CreateEmployeeInvoicesRequest;
use App\Http\Requests\UpdateEmployeeInvoicesRequest;
use App\Models\Employee;
use App\Models\EmployeeInvoices;
use App\Models\Items;
use App\Models\Transactions;
use App\Repositories\EmployeeInvoicesRepository;
use App\Traits\GlobalPagination;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Flash;
class EmployeeInvoicesController extends AppBaseController
{
    use GlobalPagination;

    private $employeeInvoicesRepository;

    public function __construct(EmployeeInvoicesRepository $employeeInvoicesRepo)
    {
        $this->employeeInvoicesRepository = $employeeInvoicesRepo;
    }

    public function index(Request $request)
    {
        $paginationParams = $this->getPaginationParams($request, $this->getDefaultPerPage());
        $query = EmployeeInvoices::query()->orderBy('billing_month', 'desc');

        if ($request->filled('id')) {
            $query->where('id', 'like', '%' . $request->id . '%');
        }
        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }
        if ($request->filled('billing_month')) {
            $billingMonth = \Carbon\Carbon::parse($request->billing_month);
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
            ? \Carbon\Carbon::parse($request->billing_month)
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
            if($request->ajax()){
                return response()->json(['success' => true, 'message' => 'Employee invoice saved successfully.']);
            }
           Flash::success('Employee invoice saved successfully.');
            return redirect()->back();
        } catch (\Exception $e) {
            DB::rollback();
            if($request->ajax()){
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

        return view('employee_invoices.show')->with('employeeInvoice', $employeeInvoice);
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

        if ($invoice->status == 1) {
            session()->flash('error', 'Cannot delete paid invoice. Only unpaid invoices can be deleted.');
            return redirect(route('employeeInvoices.index'));
        }

        Transactions::where('reference_type', 'EmployeeInvoice')->where('reference_id', $id)->delete();
        $invoice->deleted_by = Auth::id();
        $invoice->save();
        $invoice->delete();

        session()->flash('success', 'Employee invoice deleted successfully.');
        return redirect(route('employeeInvoices.index'));
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
            if (!$invoice || $invoice->status == 1) {
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
            'message' => $deleted . ' employee invoice(s) deleted successfully.',
        ]);
    }
}

