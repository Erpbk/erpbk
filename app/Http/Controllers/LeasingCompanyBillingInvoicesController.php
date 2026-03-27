<?php

namespace App\Http\Controllers;

use App\Models\Bikes;
use App\Models\LeasingCompanies;
use App\Models\LeasingCompanyBillingInvoice;
use App\Repositories\LeasingCompanyBillingInvoicesRepository;
use App\Traits\GlobalPagination;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class LeasingCompanyBillingInvoicesController extends AppBaseController
{
    use GlobalPagination;

    public function __construct(private LeasingCompanyBillingInvoicesRepository $billingInvoicesRepository)
    {
    }

    public function index(Request $request)
    {
        $paginationParams = $this->getPaginationParams($request, $this->getDefaultPerPage());
        $query = LeasingCompanyBillingInvoice::with('leasingCompany')
            ->orderBy('billing_month', 'desc')
            ->orderBy('id', 'desc');

        if ($request->has('leasing_company_id') && !empty($request->leasing_company_id)) {
            $query->where('leasing_company_id', $request->leasing_company_id);
        }
        if ($request->has('billing_month') && !empty($request->billing_month)) {
            $billingMonth = \Carbon\Carbon::parse($request->billing_month);
            $query->whereYear('billing_month', $billingMonth->year)
                ->whereMonth('billing_month', $billingMonth->month);
        }
        if ($request->has('status') && !empty($request->status)) {
            $query->where('status', $request->status);
        }

        $data = $this->applyPagination($query, $paginationParams);
        $leasingCompanies = LeasingCompanies::where('status', 1)->orderBy('name')->get();

        if ($request->ajax()) {
            $tableData = view('leasing_company_billing_invoices.table', ['data' => $data])->render();
            $paginationLinks = $data->links('components.global-pagination')->render();
            return response()->json([
                'tableData' => $tableData,
                'paginationLinks' => $paginationLinks,
            ]);
        }

        return view('leasing_company_billing_invoices.index', [
            'data' => $data,
            'leasingCompanies' => $leasingCompanies,
        ]);
    }

    public function create($id = null)
    {
        $leasingCompanyId = $id ?? request('leasing_company_id');
        $leasingCompany = null;
        if ($leasingCompanyId) {
            $leasingCompany = LeasingCompanies::find($leasingCompanyId);
            if (!$leasingCompany) {
                session()->flash('error', 'Leasing Company not found');
                return redirect(route('leasingCompanyBillingInvoices.index'));
            }
        }

        $leasingCompanies = LeasingCompanies::where('status', 1)->orderBy('name')->pluck('name', 'id')->prepend('Select', '')->toArray();
        $bikes = Bikes::where('status', 1)->orderBy('plate')->get()->mapWithKeys(function ($b) {
            return [$b->id => $b->plate . ' - ' . ($b->model ?? '')];
        })->prepend('Select', '')->toArray();

        $rentalAmountByCompany = [];

        return view('leasing_company_billing_invoices.create', compact('leasingCompany', 'bikes', 'leasingCompanies', 'rentalAmountByCompany'));
    }

    public function createFromClone($id)
    {
        $sourceInvoice = $this->billingInvoicesRepository->find($id);
        if (empty($sourceInvoice)) {
            $message = 'Source invoice not found.';
            if (request()->ajax()) {
                return response()->view('leasing_company_billing_invoices.modal_error', compact('message'), 200);
            }
            session()->flash('error', $message);
            return redirect(route('leasingCompanyBillingInvoices.index'));
        }

        $sourceInvoice->load('items');
        $nextMonth = \Carbon\Carbon::parse($sourceInvoice->billing_month)->addMonth();
        $nextMonthString = $nextMonth->format('Y-m');

        $existingInvoice = LeasingCompanyBillingInvoice::where('leasing_company_id', $sourceInvoice->leasing_company_id)
            ->whereYear('billing_month', $nextMonth->year)
            ->whereMonth('billing_month', $nextMonth->month)
            ->first();

        if ($existingInvoice) {
            $message = 'A billing invoice for this leasing company already exists for ' . $nextMonthString . '.';
            if (request()->ajax()) {
                return response()->view('leasing_company_billing_invoices.modal_error', compact('message'), 200);
            }
            session()->flash('error', $message);
            return redirect(route('leasingCompanyBillingInvoices.index'));
        }

        $leasingCompany = LeasingCompanies::find($sourceInvoice->leasing_company_id);
        $leasingCompanies = LeasingCompanies::where('status', 1)->orderBy('name')->pluck('name', 'id')->prepend('Select', '')->toArray();
        $rentalAmountByCompany = [];

        $cloneItems = [];
        $cloneBikeIds = [];
        foreach ($sourceInvoice->items as $item) {
            $bike = Bikes::withTrashed()->find($item->bike_id);
            if (!$bike) {
                continue;
            }
            $cloneBikeIds[] = $bike->id;
            $isInactive = (int) $bike->status !== 1 || $bike->trashed() || in_array($bike->warehouse ?? '', ['Return', 'Vacation', 'Express Garage', 'Absconded'], true);
            $cloneItems[] = [
                'bike_id' => $item->bike_id,
                'days' => min((int) ($item->days ?? 30), 30) ?: 30,
                'rental_amount' => (float) $item->rental_amount,
                'tax_rate' => (float) ($item->tax_rate ?? \App\Helpers\Common::getSetting('vat_percentage') ?? 5),
                'total_amount' => (float) $item->total_amount,
                'is_inactive' => $isInactive,
            ];
        }

        $companyBikes = Bikes::withTrashed()
            ->where(function ($q) use ($sourceInvoice, $cloneBikeIds) {
                $q->where('company', $sourceInvoice->leasing_company_id)
                    ->orWhereIn('id', $cloneBikeIds);
            })
            ->orderBy('plate')
            ->get();

        $bikes = [];
        foreach ($companyBikes as $b) {
            $label = $b->plate . ' - ' . ($b->model ?? '');
            $isInactive = (int) $b->status !== 1 || $b->trashed() || in_array($b->warehouse, ['Return', 'Vacation', 'Express Garage', 'Absconded'], true);
            if ($isInactive) {
                $label .= ' (Inactive/Returned)';
            }
            $bikes[$b->id] = $label;
        }
        $bikes = ['' => 'Select'] + $bikes;

        $nextBillingMonth = $nextMonthString;
        $cloneFromInvoice = (object) [
            'inv_date' => now()->format('Y-m-d'),
            'billing_month' => $nextMonthString . '-01',
            'leasing_company_id' => $sourceInvoice->leasing_company_id,
            'reference_number' => '',
            'descriptions' => $sourceInvoice->descriptions ?? '',
            'notes' => $sourceInvoice->notes ?? '',
        ];

        return view('leasing_company_billing_invoices.create', compact(
            'cloneFromInvoice',
            'cloneItems',
            'nextBillingMonth',
            'leasingCompany',
            'bikes',
            'leasingCompanies',
            'rentalAmountByCompany'
        ));
    }

    public function store(Request $request, $id = null)
    {
        try {
            $leasingCompanyId = $id ?? $request->leasing_company_id;
            $leasingCompany = LeasingCompanies::find($leasingCompanyId);
            if (!$leasingCompany) {
                return response()->json(['errors' => ['error' => 'Leasing Company not found!']], 422);
            }

            $invalidBikes = [];
            foreach ($request->bike_id ?? [] as $key => $bikeId) {
                if (empty($bikeId)) {
                    continue;
                }
                $bike = Bikes::withTrashed()->find($bikeId);
                if (!$bike) {
                    $invalidBikes[] = 'Bike ID ' . $bikeId . ' at position ' . ($key + 1) . ' does not exist.';
                }
            }

            if (!empty($invalidBikes)) {
                $msg = implode(' ', $invalidBikes);
                if ($request->ajax()) {
                    return response()->json(['errors' => ['error' => $msg]], 422);
                }
                session()->flash('error', $msg);
                return redirect()->back()->withInput();
            }

            $request->validate([
                'inv_date' => 'required|date',
                'billing_month' => 'required',
                'reference_number' => 'required|string|max:255',
                'leasing_company_invoice_number' => 'nullable|string|max:255',
                'bike_id' => 'required|array|min:1',
                'bike_id.*' => 'required',
                'rental_amount' => 'required|array|min:1',
                'rental_amount.*' => 'numeric|min:0',
                'days' => 'nullable|array',
                'days.*' => 'nullable|integer|min:1',
                'descriptions' => 'nullable|string',
                'notes' => 'nullable|string',
                'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:10240',
            ]);

            $filteredBikeIds = [];
            $filteredRentalAmounts = [];
            $filteredDays = [];
            $filteredTaxRates = [];

            foreach ($request->bike_id as $key => $bikeId) {
                if (empty($bikeId)) {
                    continue;
                }
                $filteredBikeIds[] = $bikeId;
                $filteredRentalAmounts[] = $request->rental_amount[$key] ?? 0;
                $filteredDays[] = $request->days[$key] ?? null;
                $filteredTaxRates[] = $request->tax_rate[$key] ?? \App\Helpers\Common::getSetting('vat_percentage') ?? 5;
            }

            if (empty($filteredBikeIds)) {
                $msg = 'Please add at least one bike.';
                if ($request->ajax()) {
                    return response()->json(['errors' => ['error' => $msg]], 422);
                }
                session()->flash('error', $msg);
                return redirect()->back()->withInput();
            }

            $attachmentPath = null;
            if ($request->hasFile('attachment')) {
                $file = $request->file('attachment');
                $fileName = time() . '_' . str_replace(' ', '_', $file->getClientOriginalName());
                $attachmentPath = $file->storeAs('leasing_billing_invoices', $fileName, 'public');
            }

            $mergeData = [
                'leasing_company_id' => $leasingCompanyId,
                'bike_id' => $filteredBikeIds,
                'rental_amount' => $filteredRentalAmounts,
                'days' => $filteredDays,
                'tax_rate' => $filteredTaxRates,
            ];
            if ($attachmentPath) {
                $mergeData['attachment'] = $attachmentPath;
            }
            $request->merge($mergeData);

            $invoice = $this->billingInvoicesRepository->record($request);

            if ($request->ajax()) {
                return response()->json([
                    'message' => 'Billing invoice created successfully.',
                    'redirect' => route('leasingCompanyBillingInvoices.show', $invoice->id),
                ]);
            }

            session()->flash('success', 'Billing invoice created successfully.');
            return redirect(route('leasingCompanyBillingInvoices.show', $invoice->id));
        } catch (\Exception $e) {
            if ($request->ajax()) {
                return response()->json(['errors' => ['error' => $e->getMessage()]], 422);
            }
            session()->flash('error', $e->getMessage());
            return redirect()->back()->withInput();
        }
    }

    public function show($id)
    {
        $invoice = $this->billingInvoicesRepository->find($id);
        if (empty($invoice)) {
            session()->flash('error', 'Billing invoice not found');
            return redirect(route('leasingCompanyBillingInvoices.index'));
        }
        return view('leasing_company_billing_invoices.show')->with('invoice', $invoice);
    }

    public function edit($id)
    {
        $invoice = $this->billingInvoicesRepository->find($id);
        if (empty($invoice)) {
            session()->flash('error', 'Billing invoice not found');
            return redirect(route('leasingCompanyBillingInvoices.index'));
        }

        $invoice->load('items');
        $leasingCompanies = LeasingCompanies::where('status', 1)->orderBy('name')->pluck('name', 'id')->prepend('Select', '')->toArray();
        $bikes = Bikes::where('status', 1)->orderBy('plate')->get()->mapWithKeys(function ($b) {
            return [$b->id => $b->plate . ' - ' . ($b->model ?? '')];
        })->prepend('Select', '')->toArray();
        $rentalAmountByCompany = [];

        return view('leasing_company_billing_invoices.edit', compact('invoice', 'leasingCompanies', 'bikes', 'rentalAmountByCompany'));
    }

    public function update(Request $request, $id)
    {
        try {
            $invoice = $this->billingInvoicesRepository->find($id);
            if (empty($invoice)) {
                session()->flash('error', 'Billing invoice not found');
                return redirect(route('leasingCompanyBillingInvoices.index'));
            }

            $request->validate([
                'inv_date' => 'required|date',
                'billing_month' => 'required',
                'reference_number' => 'required|string|max:255',
                'leasing_company_invoice_number' => 'required|string|max:255',
                'bike_id' => 'required|array|min:1',
                'bike_id.*' => 'required|exists:bikes,id',
                'rental_amount' => 'required|array|min:1',
                'rental_amount.*' => 'numeric|min:0',
                'days' => 'nullable|array',
                'days.*' => 'nullable|integer|min:1',
                'descriptions' => 'nullable|string',
                'notes' => 'nullable|string',
                'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:10240',
            ]);

            if ($request->hasFile('attachment')) {
                $file = $request->file('attachment');
                $fileName = time() . '_' . str_replace(' ', '_', $file->getClientOriginalName());
                $attachmentPath = $file->storeAs('leasing_billing_invoices', $fileName, 'public');
                $request->merge(['attachment' => $attachmentPath]);

                if ($invoice->attachment && Storage::disk('public')->exists($invoice->attachment)) {
                    Storage::disk('public')->delete($invoice->attachment);
                }
            }

            $invoice = $this->billingInvoicesRepository->record($request, $id);

            session()->flash('success', 'Billing invoice updated successfully.');
            if ($request->ajax()) {
                return response()->json([
                    'message' => 'Billing invoice updated successfully.',
                    'redirect' => route('leasingCompanyBillingInvoices.show', $invoice->id),
                ]);
            }
            return redirect(route('leasingCompanyBillingInvoices.show', $invoice->id));
        } catch (\Exception $e) {
            if ($request->ajax()) {
                return response()->json(['errors' => ['error' => $e->getMessage()]], 422);
            }
            session()->flash('error', $e->getMessage());
            return redirect()->back()->withInput();
        }
    }

    public function destroy($id)
    {
        if (!Gate::allows('leasing_company_invoice_delete')) {
            abort(403, 'Unauthorized action.');
        }

        $invoice = $this->billingInvoicesRepository->find($id);
        if (empty($invoice)) {
            session()->flash('error', 'Billing invoice not found');
            return redirect(route('leasingCompanyBillingInvoices.index'));
        }

        if ($invoice->status == 1) {
            session()->flash('error', 'Cannot delete paid billing invoice. Only unpaid invoices can be deleted.');
            return redirect(route('leasingCompanyBillingInvoices.index'));
        }

        try {
            DB::table('transactions')
                ->where('reference_type', 'LeasingCompanyBillingInvoice')
                ->where('reference_id', $id)
                ->delete();

            DB::table('leasing_company_billing_invoice_items')
                ->where('inv_id', $id)
                ->delete();

            if ($invoice->attachment && Storage::disk('public')->exists($invoice->attachment)) {
                Storage::disk('public')->delete($invoice->attachment);
            }

            $invoice->delete();
            session()->flash('success', 'Billing invoice deleted successfully.');
        } catch (\Exception $e) {
            session()->flash('error', 'Error deleting billing invoice: ' . $e->getMessage());
        }

        return redirect(route('leasingCompanyBillingInvoices.index'));
    }

    public function clone(Request $request, $id)
    {
        try {
            $sourceInvoice = $this->billingInvoicesRepository->find($id);
            if (empty($sourceInvoice)) {
                return response()->json(['errors' => ['error' => 'Source billing invoice not found!']], 422);
            }

            $nextMonth = \Carbon\Carbon::parse($sourceInvoice->billing_month)->addMonth();
            $nextMonthString = $nextMonth->format('Y-m');

            $existingInvoice = LeasingCompanyBillingInvoice::where('leasing_company_id', $sourceInvoice->leasing_company_id)
                ->whereYear('billing_month', $nextMonth->year)
                ->whereMonth('billing_month', $nextMonth->month)
                ->first();

            if ($existingInvoice) {
                return response()->json(['errors' => ['error' => 'A billing invoice for this leasing company already exists for ' . $nextMonthString . '.']], 422);
            }

            DB::beginTransaction();

            $newInvoiceData = $sourceInvoice->toArray();
            unset($newInvoiceData['id'], $newInvoiceData['invoice_number'], $newInvoiceData['created_at'], $newInvoiceData['updated_at'], $newInvoiceData['deleted_at']);
            $newInvoiceData['billing_month'] = $nextMonthString . '-01';
            $newInvoiceData['inv_date'] = now()->format('Y-m-d');
            $newInvoiceData['status'] = 0;

            $newInvoice = LeasingCompanyBillingInvoice::create($newInvoiceData);
            if (empty($newInvoice->invoice_number)) {
                $newInvoice->invoice_number = 'LBI-' . $newInvoice->id;
                $newInvoice->save();
            }

            foreach ($sourceInvoice->items as $item) {
                $bike = Bikes::find($item->bike_id);
                if ($bike && $bike->status == 1) {
                    $newItemData = $item->toArray();
                    unset($newItemData['id'], $newItemData['created_at'], $newItemData['updated_at']);
                    $newItemData['inv_id'] = $newInvoice->id;
                    DB::table('leasing_company_billing_invoice_items')->insert($newItemData);
                }
            }

            $items = DB::table('leasing_company_billing_invoice_items')->where('inv_id', $newInvoice->id)->get();
            $newInvoice->subtotal = $items->sum('rental_amount');
            $newInvoice->vat = $items->sum('tax_amount');
            $newInvoice->total_amount = $items->sum('total_amount');
            $newInvoice->save();

            $this->billingInvoicesRepository->recordTransactionsForInvoice($newInvoice);
            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'message' => 'Billing invoice cloned successfully for ' . $nextMonthString . '.',
                    'redirect' => route('leasingCompanyBillingInvoices.show', $newInvoice->id),
                ]);
            }

            session()->flash('success', 'Billing invoice cloned successfully for ' . $nextMonthString . '.');
            return redirect(route('leasingCompanyBillingInvoices.show', $newInvoice->id));
        } catch (\Exception $e) {
            DB::rollBack();
            if ($request->ajax()) {
                return response()->json(['errors' => ['error' => $e->getMessage()]], 422);
            }
            session()->flash('error', 'Error cloning billing invoice: ' . $e->getMessage());
            return redirect()->back();
        }
    }
}

