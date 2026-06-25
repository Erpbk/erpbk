<?php

namespace App\DataTables;

use App\Helpers\Common;
use App\Models\Accounts;
use App\Models\BikeMaintenance;
use App\Models\CustomerInvoices;
use App\Models\FuelData;
use App\Models\salik;
use App\Models\SupplierInvoices;
use App\Models\Transactions;
use App\Models\Vouchers;
use App\Support\CompanyQuery;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Services\DataTable;

class LedgerDataTable extends DataTable
{
    /**
     * Build DataTable class.
     */
    public function dataTable($query)
    {
        if (request()->has('action')) {
            @ini_set('memory_limit', '1024M');
            @set_time_limit(0);
        }
        $transactions = $query->get();
        $openingBalance = $this->getOpeningBalance();

        $data = [];
        $runningBalance = $openingBalance;
        $totalDebit = 0;
        $totalCredit = 0;

        // Add Balance Forward row at the top
        $data[] = [
            'date' => '',
            'account_name' => '',
            'reference_number' => '',
            'billing_month' => '',
            'voucher' => '',
            'narration' => '<b>Balance Forward</b>',
            'debit' => '',
            'credit' => '',
            'balance' => number_format($openingBalance, 2),
        ];

        // Process transactions and maintain running balance
        foreach ($transactions as $row) {

            $runningBalance += $row->debit - $row->credit;
            $totalDebit += $row->debit;
            $totalCredit += $row->credit;

            $view_file = '';
            $voucher_ID = '';
            $voucher_text = '';
            if (isset($row->voucher->attach_file)) {
                if ($row->reference_type == 'RTA') {
                    $view_file = '  <a href="' . url('storage/' . $row->voucher->attach_file) . '" class="no-print"  target="_blank">View File</a>';
                } elseif (in_array($row->reference_type, ['LV', 'LE'], true)) {
                    $view_file = '  <a href="' . url('storage/' . $row->voucher->attach_file) . '" class="no-print"  target="_blank">View File</a>';
                } else {
                    $view_file = '  <a href="' . url('storage/vouchers/' . $row->voucher->attach_file) . '" class="no-print"  target="_blank">View File</a>';
                }
            }
            if ($row->reference_type == 'Voucher') {
                $vouchers = CompanyQuery::table('vouchers')->where('trans_code', $row->trans_code)->first();
                if ($vouchers) {
                    $voucher_ID = $vouchers->voucher_type . '-' . str_pad($vouchers->id, 4, '0', STR_PAD_LEFT);
                    $voucher_text = '<a href="javascript:void(0);" data-title="Voucher # ' . $voucher_ID . '" data-size="xl" data-action="' . route('vouchers.show', $vouchers->id) . '" class="no-print show-modal" >' . $voucher_ID . '</a>';
                } else {
                    $voucher_text = '<span class="text-danger">No Voucher Found</span>';
                }
            }
            if ($row->reference_type == 'RTA') {
                $vouchers = CompanyQuery::table('vouchers')->where('trans_code', $row->trans_code)->first();
                if ($vouchers) {
                    $voucher_ID = $vouchers->voucher_type . '-' . str_pad($vouchers->id, 4, '0', STR_PAD_LEFT);
                    $voucher_text = '<a href="javascript:void(0);" data-title="Voucher # ' . $voucher_ID . '" data-size="xl" data-action="' . route('vouchers.show', $vouchers->id) . '" class="no-print show-modal" >' . $voucher_ID . '</a>';
                } else {
                    $voucher_text = '<span class="text-danger">No Voucher Found</span>';
                }
            }
            if (in_array($row->reference_type, ['LV', 'LE', 'VL', 'IL', 'FAV', 'FDV'], true)) {
                $vouchers = CompanyQuery::table('vouchers')->where('trans_code', $row->trans_code)->first();
                if ($vouchers) {
                    $voucher_ID = $vouchers->voucher_type . '-' . str_pad($vouchers->id, 4, '0', STR_PAD_LEFT);
                    $voucher_text = '<a href="javascript:void(0);" data-title="Voucher # ' . $voucher_ID . '" data-size="xl" data-action="' . route('vouchers.show', $vouchers->id) . '" class="no-print show-modal" >' . $voucher_ID . '</a>';
                } else {
                    $voucher_text = '<span class="text-danger">No Voucher Found</span>';
                }
            }
            if ($row->reference_type == 'INC') {
                $vouchers = CompanyQuery::table('vouchers')->where('trans_code', $row->trans_code)->first();
                if ($vouchers) {
                    $voucher_ID = $vouchers->voucher_type . '-' . str_pad($vouchers->id, 4, '0', STR_PAD_LEFT);
                    $voucher_text = '<a href="javascript:void(0);" data-title="Voucher # ' . $voucher_ID . '" data-size="xl" data-action="' . route('vouchers.show', $vouchers->id) . '" class="no-print show-modal" >' . $voucher_ID . '</a>';
                } else {
                    $voucher_text = '<span class="text-danger">No Voucher Found</span>';
                }
            }
            if ($row->reference_type == 'PN') {
                $vouchers = CompanyQuery::table('vouchers')->where('trans_code', $row->trans_code)->first();
                if ($vouchers) {
                    $voucher_ID = $vouchers->voucher_type . '-' . str_pad($vouchers->id, 4, '0', STR_PAD_LEFT);
                    $voucher_text = '<a href="javascript:void(0);" data-title="Voucher # ' . $voucher_ID . '" data-size="xl" data-action="' . route('vouchers.show', $vouchers->id) . '" class="no-print show-modal" >' . $voucher_ID . '</a>';
                } else {
                    $voucher_text = '<span class="text-danger">No Voucher Found</span>';
                }
            }
            if ($row->reference_type == 'PAY') {
                $vouchers = CompanyQuery::table('vouchers')->where('trans_code', $row->trans_code)->first();
                if ($vouchers) {
                    $voucher_ID = $vouchers->voucher_type . '-' . str_pad($vouchers->id, 4, '0', STR_PAD_LEFT);
                    $voucher_text = '<a href="javascript:void(0);" data-title="Voucher # ' . $voucher_ID . '" data-size="xl" data-action="' . route('vouchers.show', $vouchers->id) . '" class="no-print show-modal" >' . $voucher_ID . '</a>';
                } else {
                    $voucher_text = '<span class="text-danger">No Voucher Found</span>';
                }
            }
            if ($row->reference_type == 'COD') {
                $vouchers = CompanyQuery::table('vouchers')->where('trans_code', $row->trans_code)->first();
                if ($vouchers) {
                    $voucher_ID = $vouchers->voucher_type . '-' . str_pad($vouchers->id, 4, '0', STR_PAD_LEFT);
                    $voucher_text = '<a href="javascript:void(0);" data-title="Voucher # ' . $voucher_ID . '" data-size="xl" data-action="' . route('vouchers.show', $vouchers->id) . '" class="no-print show-modal" >' . $voucher_ID . '</a>';
                } else {
                    $voucher_text = '<span class="text-danger">No Voucher Found</span>';
                }
            }
            if (in_array($row->reference_type, ['Salik Voucher', 'Salik Payment'], true)) {
                $vouchers = CompanyQuery::table('vouchers')->where('trans_code', $row->trans_code)->first();
                if ($vouchers) {
                    $voucher_ID = $vouchers->voucher_type . '-' . str_pad($vouchers->id, 4, '0', STR_PAD_LEFT);
                    $voucher_text = '<a href="javascript:void(0);" data-title="Voucher # ' . $voucher_ID . '" data-size="xl" data-action="' . route('vouchers.show', $vouchers->id) . '" class="no-print show-modal" >' . $voucher_ID . '</a>';
                } else {
                    $voucher_text = '<span class="text-danger">No Voucher Found</span>';
                }
            }
            if ($row->reference_type == 'salik') {
                $salikRecord = salik::find($row->reference_id);
                if ($salikRecord && $salikRecord->rider_id && $salikRecord->billing_month) {
                    $voucher_ID = $salikRecord->inv_id ?? 'SLK-' . str_pad($salikRecord->id, 4, '0', STR_PAD_LEFT);
                    $billingMonth = Carbon::parse($salikRecord->billing_month)->format('Y-m');
                    $voucher_text = '<a href="javascript:void(0);" data-action="' . route('salik.rider_monthly_summary', [$salikRecord->rider_id, $billingMonth]) . '" class="no-print show-modal-right" data-size="xl" data-title="Salik Invoice ' . $voucher_ID . '">' . $voucher_ID . '</a>';
                } else {
                    $voucher_text = '<span class="text-danger">Salik invoice not found</span>';
                }
            }
            if ($row->reference_type == 'VC') {
                $vouchers = CompanyQuery::table('vouchers')->where('trans_code', $row->trans_code)->first();
                if ($vouchers) {
                    $voucher_ID = $vouchers->voucher_type . '-' . str_pad($vouchers->id, 4, '0', STR_PAD_LEFT);
                    $voucher_text = '<a href="javascript:void(0);" data-title="Voucher # ' . $voucher_ID . '" data-size="xl" data-action="' . route('vouchers.show', $vouchers->id) . '" class="no-print show-modal" >' . $voucher_ID . '</a>';
                } else {
                    $voucher_text = '<span class="text-danger">No Voucher Found</span>';
                }
            }
            if ($row->reference_type == 'AL') {
                $vouchers = CompanyQuery::table('vouchers')->where('trans_code', $row->trans_code)->first();
                if ($vouchers) {
                    $voucher_ID = $vouchers->voucher_type . '-' . str_pad($vouchers->id, 4, '0', STR_PAD_LEFT);
                    $voucher_text = '<a href="javascript:void(0);" data-title="Voucher # ' . $voucher_ID . '" data-size="xl" data-action="' . route('vouchers.show', $vouchers->id) . '" class="no-print show-modal" >' . $voucher_ID . '</a>';
                } else {
                    $voucher_text = '<span class="text-danger">No Voucher Found</span>';
                }
            }
            if ($row->reference_type == 'Invoice') {
                $invoice_ID = $row->reference_id;
                $voucher_text = '<a href="javascript:void(0);" data-title="Invoice # ' . $invoice_ID . '" data-size="xl" data-action="' . route('riderInvoices.show', $invoice_ID) . '" class="no-print show-modal">RD-' . $invoice_ID . '</a>';
            }
            if ($row->reference_type == 'RiderInvoice') {
                $vouchers = CompanyQuery::table('vouchers')->where('trans_code', $row->trans_code)->first();
                if ($vouchers) {
                    $voucher_ID = $vouchers->voucher_type . '-' . str_pad($vouchers->id, 4, '0', STR_PAD_LEFT);
                    $voucher_text = '<a href="javascript:void(0);" data-title="Voucher # ' . $voucher_ID . '" data-size="xl" data-action="' . route('vouchers.show', $vouchers->id) . '" class="no-print show-modal" >' . $voucher_ID . '</a>';
                } else {
                    $voucher_text = '<span class="text-danger">No Voucher Found</span>';
                }
            }
            if ($row->reference_type == 'PV') {
                $vouchers = CompanyQuery::table('vouchers')->where('trans_code', $row->trans_code)->first();
                if ($vouchers) {
                    $voucher_ID = $vouchers->voucher_type . '-' . str_pad($vouchers->id, 4, '0', STR_PAD_LEFT);
                    $voucher_text = '<a href="javascript:void(0);" data-title="Voucher # ' . $voucher_ID . '" data-size="xl" data-action="' . route('vouchers.show', $vouchers->id) . '" class="no-print show-modal" >' . $voucher_ID . '</a>';
                } else {
                    $voucher_text = '<span class="text-danger">No Voucher Found</span>';
                }
            }
            if ($row->reference_type == 'Bike Maintenance') {
                $maintenance = BikeMaintenance::where('id', $row->reference_id)->first();
                if ($maintenance) {
                    $voucher_ID = 'MA-' . $maintenance->id;
                    $voucher_text = '<a href="' . route('bike-maintenance.invoice', $maintenance) . '" target="_blank" class="no-print" >' . $voucher_ID . '</a>';
                    if ($maintenance->attachment) {
                        $view_file = '  <a href="' . storage_url($maintenance->attachment) . '" class="no-print"  target="_blank">View File</a>';
                    }
                } else {
                    $voucher_ID = 'MA-' . ($row->reference_id ?? '?');
                    $voucher_text = '<span class="text-danger">Maintenance record not found</span>';
                }
            }
            if ($row->reference_type == 'CI') {
                $invoice = CustomerInvoices::where('id', $row->reference_id)->first();
                if ($invoice) {
                    $voucher_ID = $invoice->invoice_number;
                    $voucher_text = '<a href="' . route('customer_invoices.show', $invoice->id) . '" target="_blank" class="no-print" >' . $voucher_ID . '</a>';
                    if ($invoice->attachment) {
                        $view_file = '  <a href="' . storage_url($invoice->attachment) . '" class="no-print"  target="_blank">View File</a>';
                    }
                } else {
                    $voucher_ID = 'CI-' . ($row->reference_id ?? '?');
                    $voucher_text = '<span class="text-danger">Customer invoice not found</span>';
                }
            }
            if ($row->reference_type == 'SUP') {
                $invoice = SupplierInvoices::where('id', $row->reference_id)->first();
                if ($invoice) {
                    $voucher_ID = $invoice->invoice_number;
                    $voucher_text = '<a href="' . route('supplier_invoices.show', $invoice->id) . '" target="_blank" class="no-print" >' . $voucher_ID . '</a>';
                    if ($invoice->attachment) {
                        $view_file = '  <a href="' . storage_url($invoice->attachment) . '" class="no-print"  target="_blank">View File</a>';
                    }
                } else {
                    $voucher_ID = 'SUP' . ($row->reference_id ?? '?');
                    $voucher_text = '<span class="text-danger">Supplier invoice not found</span>';
                }
            }
            if ($row->reference_type == 'SimInvoice') {
                $invoice = CompanyQuery::table('sim_invoices')->where('id', $row->reference_id)->first();
                if ($invoice) {
                    $voucher_ID = $invoice->invoice_number;
                    $voucher_text = '<a href="' . route('sim_invoices.show', $invoice->id) . '" target="_blank" class="no-print" >' . $voucher_ID . '</a>';
                    if ($invoice->attachment) {
                        $view_file = '  <a href="' . storage_url($invoice->attachment) . '" class="no-print"  target="_blank">View File</a>';
                    }
                } else {
                    $voucher_ID = 'SUP' . ($row->reference_id ?? '?');
                    $voucher_text = '<span class="text-danger">Supplier invoice not found</span>';
                }
            }
            if ($row->reference_type == 'EmployeeInvoice') {
                $invoice = EmployeeInvoices::where('id', $row->reference_id)->first();
                if ($invoice) {
                    $voucher_ID = $invoice->invoice_number;
                    $voucher_text = '<a href="' . route('employee_invoices.show', $invoice->id) . '" target="_blank" class="no-print" >' . $voucher_ID . '</a>';
                    if ($invoice->attachment) {
                        $view_file = '  <a href="' . storage_url($invoice->attachment) . '" class="no-print"  target="_blank">View File</a>';
                    }
                } else {
                    $voucher_ID = 'EMP_INV' . (str_pad($row->reference_id, 4, '0', STR_PAD_LEFT) ?? '?');
                    $voucher_text = '<span class="text-danger">Employee invoice not found</span>';
                }
            }
            if ($row->reference_type == 'fuel') {
                $invoice = FuelData::find($row->reference_id);
                if ($invoice) {
                    $voucher_ID = $invoice->inv_id;
                    $voucher_text = '<a href="javascript:void(0);" data-action="' . route('fuel_data.show', $invoice->id) . '" class="no-print show-modal-right" >' . $voucher_ID . '</a>';
                    if ($invoice->attachment) {
                        $view_file = '  <a href="' . storage_url($invoice->attachment) . '" class="no-print"  target="_blank">View File</a>';
                    }
                } else {
                    $voucher_ID = 'Fuel' . ($row->reference_id ?? '?');
                    $voucher_text = '<span class="text-danger">fuel invoice not found</span>';
                }
            }
            if ($row->reference_type == 'PV') {
                $vouchers = Vouchers::where('trans_code', $row->trans_code)->first();
                if ($vouchers) {
                    $voucher_ID = $vouchers->voucher_type . '-' . str_pad($vouchers->id, 4, '0', STR_PAD_LEFT);
                    $voucher_text = '<a href="javascript:void(0);" data-title="Voucher # ' . $voucher_ID . '" data-size="xl" data-action="' . route('vouchers.show', $vouchers->id) . '" class="no-print show-modal" >' . $voucher_ID . '</a>';
                    if ($vouchers->attach_file) {
                        $view_file = '  <a href="' . storage_url($vouchers->attach_file) . '" class="no-print"  target="_blank">View File</a>';
                    }
                } else {
                    $voucher_text = '<span class="text-danger">No Voucher Found</span>';
                }
            }
            if ($row->reference_type == 'RV') {
                $vouchers = Vouchers::where('trans_code', $row->trans_code)->first();
                if ($vouchers) {
                    $voucher_ID = $vouchers->voucher_type . '-' . str_pad($vouchers->id, 4, '0', STR_PAD_LEFT);
                    $voucher_text = '<a href="javascript:void(0);" data-title="Voucher # ' . $voucher_ID . '" data-size="xl" data-action="' . route('vouchers.show', $vouchers->id) . '" class="no-print show-modal" >' . $voucher_ID . '</a>';
                    if ($vouchers->attach_file) {
                        $view_file = '  <a href="' . storage_url($vouchers->attach_file) . '" class="no-print"  target="_blank">View File</a>';
                    }
                } else {
                    $voucher_text = '<span class="text-danger">No Voucher Found</span>';
                }
            }
            if ($row->reference_type == 'LeasingCompanyInvoice') {
                $invoice_ID = $row->reference_id;
                $voucher_text = '<a href="javascript:void(0);" data-title="Leasing Company Invoice # ' . $invoice_ID . '" data-size="xl" data-action="' . route('leasingCompanyInvoices.show', $invoice_ID) . '" class="no-print show-modal">LI-' . str_pad($invoice_ID, 4, '0', STR_PAD_LEFT) . '</a>';
            }
            if ($row->reference_type == 'LeasingCompanyBillingInvoice' || $row->reference_type == 'Rental Invoice') {
                $invoice_ID = $row->reference_id;
                $voucher_text = '<a href="javascript:void(0);" data-title="Leasing Billing Invoice # ' . $invoice_ID . '" data-size="xl" data-action="' . route('leasingCompanyBillingInvoices.show', $invoice_ID) . '" class="no-print show-modal">RBI-' . str_pad($invoice_ID, 4, '0', STR_PAD_LEFT) . '</a>';
            }
            $month = "<span style='white-space: nowrap;'>" . date('M Y', strtotime($row->billing_month)) . '</span>';
            if ($row->reference_type == 'RTA') {
                $vouchers = CompanyQuery::table('vouchers')->where('trans_code', $row->trans_code)->first();

                if ($vouchers) {
                    $fines = CompanyQuery::table('rta_fines')->where('id', $vouchers->ref_id)->first();

                    if ($fines) {
                        $naration = $row->narration . ', <b>Ticket Number: </b>' . $fines->ticket_no . ', <b>Bike No: </b>' . $fines->plate_no . ', ' . Carbon::parse($fines->trip_date)->format('d M Y') . ', ' . $view_file;
                    } else {
                        $naration = $row->narration . ', ' . $view_file;
                    }
                } else {
                    $naration = $row->narration . ', ' . $view_file;
                }
            } elseif ($row->reference_type == 'LV') {
                $visaex = CompanyQuery::table('visa_expenses')->where('id', $row->reference_id)->first();
                if ($visaex) {
                    $rider = CompanyQuery::table('accounts')->where('id', $visaex->rider_id)->first();
                    if ($rider) {
                        $naration = 'Paid to <b>' . $rider->name . ' </b>' . $visaex->visa_status . ' Charges ' . $visaex->date . $view_file;
                    } else {
                        $naration = $row->narration . ' (Rider not found) ' . $view_file;
                    }
                } else {
                    $naration = $row->narration . ' (Visa expense not found) ' . $view_file;
                }
            } elseif ($row->reference_type == 'LE') {
                $licenseex = CompanyQuery::table('license_expenses')->where('id', $row->reference_id)->first();
                if ($licenseex) {
                    $rider = CompanyQuery::table('accounts')->where('id', $licenseex->rider_id)->first();
                    if ($rider) {
                        $naration = 'Paid to <b>' . $rider->name . ' </b>' . $licenseex->license_status . ' Charges ' . $licenseex->date . $view_file;
                    } else {
                        $naration = $row->narration . ' (Rider not found) ' . $view_file;
                    }
                } else {
                    $naration = $row->narration . ' (License expense not found) ' . $view_file;
                }
            } elseif ($row->reference_type == 'IL') {
                $assignment = CompanyQuery::table('rider_inventory_assignments')->where('id', $row->reference_id)->first();
                if ($assignment) {
                    $item = CompanyQuery::table('rider_inventory_items')->where('id', $assignment->inventory_item_id)->first();
                    $rider = CompanyQuery::table('riders')->where('id', $assignment->rider_id)->first();
                    $itemName = $item->name ?? 'Inventory Item';
                    $riderName = $rider->name ?? 'Rider';
                    $naration = 'Inventory loss: <b>' . $itemName . '</b> charged to <b>' . $riderName . '</b>' . $view_file;
                } else {
                    $naration = $row->narration . ' (Inventory assignment not found) ' . $view_file;
                }
            } else {
                $naration = $row->narration . ', ' . $view_file;
            }
            $reference = '-';
            if ($row->voucher) {
                $reference = $row->voucher->reference_number ?? '-';
            }
            $accountCode = $row->account ? ($row->account->account_code ?? 'N/A') : 'N/A';
            $accountName = $row->account ? ($row->account->name ?? 'N/A') : 'N/A';
            $data[] = [
                'date' => "<span style='white-space: nowrap;'>" . Common::DateFormat($row->trans_date) . '</span>',
                'account_name' => $accountCode . '-' . $accountName,
                'reference_number' => $reference,
                'billing_month' => $month,
                'voucher' => $voucher_text,
                'narration' => $naration,
                'debit' => number_format($row->debit, 2),
                'credit' => number_format($row->credit, 2),
                'balance' => number_format($runningBalance, 2),
            ];
        }
        $data[] = [
            'date' => '',
            'account_name' => '',
            'reference_number' => '',
            'billing_month' => '',
            'voucher' => '',
            'narration' => '<b>Total</b>',
            'debit' => '<b>' . number_format($totalDebit, 2) . '</b>',
            'credit' => '<b>' . number_format($totalCredit, 2) . '</b>',
            'balance' => '<b>' . number_format($runningBalance, 2) . '</b>',
        ];

        return datatables()->of($data)->rawColumns(['date', 'debit', 'credit', 'balance', 'narration', 'voucher', 'billing_month']);
    }

    /**
     * Get query source of dataTable.
     */
    public function query(Transactions $model)
    {
        $query = $model->newQuery()->with(['account', 'voucher']);

        if (request('account')) {
            $query->where('account_id', request('account'));
        }
        if ($this->account_id) {
            $query->where('account_id', $this->account_id);
        }

        if (request('month') && ! request('from_date') && ! request('to_date')) {
            $query->where('billing_month', request('month') . '-01');
        }

        if (request('from_date') && request('to_date')) {
            $query->whereBetween('trans_date', [request('from_date'), request('to_date')]);
        }

        $query = $query->orderBy('billing_month', 'ASC')->orderBy('trans_date', 'ASC');

        return $query;
    }

    /**
     * Get Opening Balance before the selected date.
     */
    private function getOpeningBalance()
    {
        if (! request('month') && ! request('from_date') && ! request('to_date')) {
            return 0;
        }

        if (request('account')) {
            $account_id = request('account');
        } else {
            $account_id = $this->account_id;
        }
        if (! $account_id) {
            return 0;
        }

        if (request('from_date') && request('to_date')) {
            return Transactions::where('account_id', $account_id)
                ->where('trans_date', '<', request('from_date'))
                ->sum(DB::raw('debit - credit'));
        }

        return Transactions::where('account_id', $account_id)
            ->whereDate('billing_month', '<', request('month') . '-01')
            ->sum(DB::raw('debit - credit'));
    }

    /**
     * Optional method if you want to use HTML builder.
     */
    public function html()
    {
        $accountid = '';
        $accountName = 'All Accounts';
        if ($this->account_id) {
            $account = Accounts::find($this->account_id);
            $accountid = $account?->id ?? null;
            $accountName = $account ? $account->account_code . '-' . $account->name : 'All Accounts';
        }

        return $this->builder()
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->parameters([
                'dom' => "<'row'<'col-md-6'><'col-md-6 d-flex justify-content-end'B>>" . // Export buttons fully right-aligned
                    "<'row'<'col-md-6'><'col-md-6'f>>" . // Search box on the right
                    "<'row'<'col-md-12'tr>>" .
                    "<'row'<'col-md-5'i l><'col-md-7'p>>", // Info (left) and Pagination (right)
                'order' => [[0, 'asc']], // Order by date ascending
                'ordering' => false,
                'pageLength' => 50,
                'lengthMenu' => [
                    [50, 100, 150, 200, -1], // Values to display in the dropdown
                    [50, 100, 150, 200, 'All'], // Labels for the dropdown
                ],
                'stateSave' => false,
                'responsive' => true,
                'footerCallback' => "function(row, data, start, end, display) {
                    var api = this.api();
                    var intVal = function(i) {
                        return typeof i === 'string' ? parseFloat(i.replace(/[\$,]/g, '')) : (typeof i === 'number' ? i : 0);
                    };

                    totalDebit = api.column(6, { page: 'current' }).data().reduce(function(a, b) { return intVal(a) + intVal(b); }, 0);
                    totalCredit = api.column(7, { page: 'current' }).data().reduce(function(a, b) { return intVal(a) + intVal(b); }, 0);
                    totalBalance = api.column(8, { page: 'current' }).data().reduce(function(a, b) { return intVal(a) + intVal(b); }, 0);

                    $(api.column(5).footer()).html('<b>' + totalDebit.toFixed(2) + '</b>');
                    $(api.column(6).footer()).html('<b>' + totalCredit.toFixed(2) + '</b>');
                    $(api.column(7).footer()).html('<b>' + totalBalance.toFixed(2) + '</b>');
                }",
                'initComplete' => "function(settings, json) {

                  window.getLedgerParams = function(){
                    var params = new URLSearchParams(window.location.search);

                    if(!params.get('account')){
                        params.set('account', '{$accountid}');
                    }

                    return params.toString();
                  }

                }",
                'buttons' => [
                    [
                        'text' => '<i class="fa fa-file-excel"></i>&nbsp;Export to Excel',
                        'className' => 'btn btn-success btn-sm no-corner',
                        'action' => 'function(e, dt, button, config) {
                          var url = "' . route('ledger.export') . '?" + getLedgerParams();
                          window.location.href = url;
                        }',
                    ],
                    [
                        'text' => '<i class="fa fa-print"></i> Print Statement',
                        'className' => 'btn btn-primary btn-sm',

                        'action' => 'function(){

                            window.open("' . route('ledger.print') . '?" + getLedgerParams(), "_blank");

                        }',
                    ],
                ],
                /* 'language' => [
              'processing' => '<div class="loading-overlay"><div class="spinner-border text-primary" role="status"></div></div>'
            ], */
            ]);
    }

    /**
     * Get columns.
     */
    protected function getColumns()
    {
        return [
            'date',
            'account_name' => ['title' => 'Account'],
            'reference_number' => ['title' => 'Reference'],
            'billing_month' => ['title' => 'Month'],
            'voucher',
            'narration',
            'debit',
            'credit',
            'balance',
        ];
    }

    /**
     * Get filename for export.
     */
    protected function filename(): string
    {
        return 'ledger_datatable_' . time();
    }
}
