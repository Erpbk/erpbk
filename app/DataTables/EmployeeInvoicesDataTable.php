<?php

namespace App\DataTables;

use App\Helpers\Common;
use App\Models\EmployeeInvoices;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Services\DataTable;

class EmployeeInvoicesDataTable extends DataTable
{
    public function dataTable($query)
    {
        $dataTable = new EloquentDataTable($query);

        $dataTable
            ->addColumn('invoice_number', function (EmployeeInvoices $invoice) {
                $url = safe_route('employeeInvoices.show', $invoice->id, 'employeeInvoices/{id}');
                $label = e($invoice->invoice_number);

                return '<a href="javascript:void(0);" data-action="'.e($url).'" data-size="xl" class="show-modal-right">'.$label.'</a>';
            })
            ->addColumn('inv_date', function (EmployeeInvoices $invoice) {
                return $invoice->inv_date ? Common::DateFormat($invoice->inv_date) : '';
            })
            ->addColumn('billing_month', function (EmployeeInvoices $invoice) {
                return date('M Y', strtotime($invoice->billing_month));
            })
            ->editColumn('descriptions', function (EmployeeInvoices $invoice) {
                return e((string) ($invoice->descriptions ?? ''));
            })
            ->addColumn('status', function (EmployeeInvoices $invoice) {
                return view('invoices.partials.status_badge', [
                    'status' => $invoice->paymentStatusKey(),
                ])->render();
            })
            ->addColumn('paid_amount', function (EmployeeInvoices $invoice) {
                return number_format((float) $invoice->paid_amount, 2);
            })
            ->addColumn('balance', function (EmployeeInvoices $invoice) {
                return number_format((float) $invoice->balance, 2);
            });

        $dataTable->filterColumn('inv_date', function ($query, $keyword) {
            $query->whereRaw("DATE_FORMAT(inv_date, '%d-%m-%Y') LIKE ?", ["%{$keyword}%"])
                ->orWhereRaw("DATE_FORMAT(inv_date, '%Y-%m-%d') LIKE ?", ["%{$keyword}%"]);
        });

        $dataTable->filterColumn('billing_month', function ($query, $keyword) {
            $query->whereRaw("DATE_FORMAT(billing_month, '%b %Y') like ?", ["%{$keyword}%"]);
        });

        $dataTable->filterColumn('invoice_number', function ($query, $keyword) {
            $keyword = trim((string) $keyword);
            if ($keyword === '') {
                return;
            }

            if (preg_match('/^(?:EMP[_-]?INV-?)?0*(\d+)$/i', $keyword, $matches)) {
                $query->where('id', (int) $matches[1]);

                return;
            }

            $query->where('id', 'like', '%'.$keyword.'%');
        });

        $dataTable->rawColumns(['invoice_number', 'status']);

        return $dataTable;
    }

    public function query(EmployeeInvoices $model)
    {
        $query = $model->newQuery()->with(['employee']);

        if ($this->employee_id) {
            $query->where('employee_id', $this->employee_id);
        }
        if (request('month')) {
            $query->where(DB::raw('DATE_FORMAT(billing_month, "%Y-%m")'), '=', request('month'));
        }
        if (request('employee_id')) {
            $query->where('employee_id', request('employee_id'));
        }

        return $query;
    }

    public function html()
    {
        return $this->builder()
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->parameters([
                'dom' => "<'row'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" .
                    "<'row'<'col-sm-12'tr>>" .
                    "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
                'stateSave' => false,
                'ordering' => false,
                'pageLength' => 50,
                'scrollX' => true,
                'autoWidth' => false,
                'responsive' => false,
                'order' => [[0, 'desc']],
                'buttons' => [],
                'language' => [
                    'processing' => '<div class="loading-overlay"><div class="spinner-border text-primary" role="status"></div></div>',
                ],
            ]);
    }

    protected function getColumns()
    {
        return [
            'invoice_number' => ['title' => 'Invoice No', 'width' => '110px'],
            'inv_date' => ['title' => 'Inv Date', 'width' => '110px'],
            'billing_month' => ['title' => 'Billing Month', 'width' => '110px'],
            'descriptions' => [
                'title' => 'Descriptions',
                'className' => 'col-descriptions',
            ],
            'total_amount' => ['title' => 'Total', 'width' => '100px'],
            'paid_amount' => ['title' => 'Paid Amount', 'width' => '110px'],
            'balance' => ['title' => 'Balance', 'width' => '100px'],
            'status' => ['title' => 'Status', 'width' => '90px'],
        ];
    }

    protected function filename(): string
    {
        return 'employee_invoices_datatable_'.time();
    }
}
