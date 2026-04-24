<?php

namespace App\DataTables;

use App\Helpers\General;
use App\Models\Riders;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\EloquentDataTable;
use Carbon\Carbon;

class RidersDataTable extends DataTable
{
  public function dataTable($query)
  {
    $dataTable = new EloquentDataTable($query);

    $dataTable
      ->addColumn('action', 'riders.datatables_actions')
      ->addColumn('status', function (Riders $rider) {
        $statusText = General::RiderStatus($rider->status);
        $badgeClass = ($rider->status == 1) ? 'bg-label-success' : 'bg-label-danger';
        return '<span class="badge ' . $badgeClass . '">' . $statusText . '</span>';
      })
      /* ->addColumn('job_status', function (Riders $rider) {
        $statusText = General::JobStatus($rider->job_status);
        $badgeClass = ($rider->job_status == 1) ? 'bg-label-success' : 'bg-label-info';
        return '<span class="badge ' . $badgeClass . '">' . $statusText . '</span>';
      }) */
      ->addColumn('name', function (Riders $rider) {
        /* $phone = preg_replace('/[^0-9]/', '', $rider->company_contact);
        $whatsappNumber = '+971' . ltrim($phone, '0'); */
        $name = '<a href="' . route('riders.show', $rider->id) . '">' . $rider->name . '</a><br/>';
        /*  if (!$rider->company_contact) {
           $name .= 'Contact: N/A<br/>';
         } else {
           $phone = preg_replace('/[^0-9]/', '', $rider->company_contact);
           $whatsappNumber = '+971' . ltrim($phone, '0');

           $name .= 'Contact: <a href="https://wa.me/' . $whatsappNumber . '" target="_blank" class="text-success">
                         <i class="fab fa-whatsapp"></i> ' . $rider->company_contact . '
                     </a><br/>';
         }
         $name .= 'HUB: ' . $rider->emirate_hub; */

        return $name;
      })
      ->editColumn('bike', function (Riders $rider) {
        return $rider->bikes->plate ?? '-';
      })
      ->editColumn('customer_id', function (Riders $rider) {
        return $rider->customer->name ?? '-';
      })
      ->editColumn('orders_sum', function ($rider) {
        return $rider->orders_sum ?? '-';
      })
      /*  ->addColumn('hr', function (Riders $rider) {
         return $rider->activity->sum('login_hr') ?? '-';
       }) */
      ->editColumn('days', function (Riders $rider) {
        return $rider->activity->count('date') ?? '-';
      })
      ->editColumn('attendance', function (Riders $rider) {
        $attn = General::getAttnActivity($rider->id);

        $job_status = '';
        if ($attn['timeline']) {
          $job_status .= '<a href="' . route('rider.timeline', $rider->id) . '"><span class="text-danger cursor-pointer" title="Timeline Added">●</span></a>&nbsp;';
        }
        if ($attn['emails']) {
          $job_status .= '<a href="' . route('rider.emails', $rider->id) . '"><span class="text-success cursor-pointer" title="Email Sent">●</span></a>&nbsp;';
        }
        $job_status .= '<a href="javascript:void(0);" data-action="' . url('riders/job_status/' . $rider->id) . '" data-size="md" data-title="Add Timeline" class="show-modal">' . $rider->attendance . '</a>';
        return $job_status;
      })
      // Status filter
      ->filterColumn('status', function ($query, $keyword) {
        $searchTerm = strtolower(trim($keyword));
        if ($searchTerm === 'active') {
          $query->where('status', 1);
        } elseif ($searchTerm === 'inactive') {
          $query->where('status', 3);
        } else {
          if (str_contains($searchTerm, 'inactive')) {
            $query->where('status', 3);
          } elseif (str_contains($searchTerm, 'active')) {
            $query->where('status', 1);
          }
        }
      })
      // Name filter (CRUCIAL FIX)
      ->filterColumn('name', function ($query, $keyword) {
        $query->where('name', 'LIKE', "%{$keyword}%");
      })
      // Fleet Supervisor filter
      ->filterColumn('fleet_supervisor', function ($query, $keyword) {
        $query->where('fleet_supervisor', 'LIKE', "%{$keyword}%");
      })
      ->filterColumn('customer_id', function ($query, $keyword) {
        $query->whereHas('customer', function ($q) use ($keyword) {
          $q->where('name', 'like', "%{$keyword}%");
        });
      })

      ->rawColumns(['name', 'status', 'action', 'attendance']);

    return $dataTable;
  }
  public function query(Riders $model)
  {


    $currentMonthStart = Carbon::now()->startOfMonth()->toDateString();
    $currentMonthEnd = Carbon::now()->endOfMonth()->toDateString();

    $query = $model->newQuery()
      ->leftJoin('rider_activities', function ($join) use ($currentMonthStart, $currentMonthEnd) {
        $join->on('riders.id', '=', 'rider_activities.rider_id')
          ->whereBetween('rider_activities.date', [$currentMonthStart, $currentMonthEnd]);
      })
      ->select([
        'riders.id',
        'riders.rider_id',
        'riders.name',
        'riders.fleet_supervisor',
        'riders.status',
        'riders.customer_id',
        'riders.attendance',
        \DB::raw('SUM(rider_activities.delivered_orders) as orders_sum'),
        \DB::raw('COUNT(rider_activities.date) as days')
      ]);
    if (request('fleet')) {
      $query->where('fleet_supervisor', request('fleet'));
    }
    $query->groupBy([
      'riders.id',
      'riders.rider_id',
      'riders.name',
      'riders.fleet_supervisor',
      'riders.status',
      'riders.customer_id',
      'riders.attendance'
    ]);
    return $query;
  }

  public function html()
  {
    return $this->builder()
      ->columns($this->getColumns())
      ->minifiedAjax()
      ->addAction(['width' => '120px', 'printable' => false])
      ->parameters([
        'dom' => 'Bfrtip',
        'stateSave' => false,
        'order' => [[0, 'desc']],
        'pageLength' => 50,
        'responsive' => true,
        'buttons' => [ // Enable Buttons as per your need
          //                    ['extend' => 'create', 'className' => 'btn btn-default btn-sm no-corner',],
          // ['extend' => 'export', 'className' => 'btn btn-default btn-sm no-corner',],
          //['extend' => 'excel', 'className' => 'btn btn-success btn-sm no-corner'],
          //                    ['extend' => 'print', 'className' => 'btn btn-default btn-sm no-corner',],
          //                    ['extend' => 'reset', 'className' => 'btn btn-default btn-sm no-corner',],
        ],
        'initComplete' => "function () {
                    this.api().columns().every(function () {
                        var column = this;
                        var header = $(column.header());

                        if (header.text() === 'Status') {
                            var input = $('<input type=\"text\" placeholder=\"Search Status\" class=\"form-control form-control-sm\"/>')
                                .appendTo(header)
                                .on('keyup change clear', function () {
                                    column.search($(this).val()).draw();
                                });
                        }
                    });
                }",
        'language' => [
          'processing' => '<div class="loading-overlay"><div class="spinner-border text-primary" role="status"></div></div>'
        ],
      ]);
  }

  protected function getColumns()
  {
    return [
      [
        'data' => 'rider_id',
        'title' => 'Rider ID',
        'searchable' => true,
        'orderable' => true
      ],
      [
        'data' => 'name',
        'title' => 'Name',
        'searchable' => true,
        'orderable' => true
      ],
      [
        'data' => 'fleet_supervisor',
        'title' => 'Fleet Supv',
        'searchable' => true,
        'orderable' => true
      ],
      [
        'data' => 'customer_id',
        'title' => 'Customer',
        'searchable' => true,
        'orderable' => true
      ],
      [
        'data' => 'bike',
        'title' => 'Bike',
        'searchable' => false,
        'orderable' => false
      ],
      [
        'data' => 'status',
        'title' => 'Status',
        'searchable' => true,
        'orderable' => true
      ],
      [
        'data' => 'attendance',
        'title' => 'ATTN',
        'searchable' => true,
        'orderable' => true
      ],
      [
        'data' => 'orders_sum',
        'name' => 'orders_sum',
        'title' => 'Orders',
        'searchable' => false,
        'orderable' => true
      ],
      /* [
        'data' => 'hr',
        'title' => 'HR',
        'searchable' => true,
        'orderable' => true
      ], */
      [
        'data' => 'days',
        'title' => 'Days',
        'searchable' => false,
        'orderable' => true
      ]
    ];
  }

  protected function filename(): string
  {
    return 'riders_datatable_' . time();
  }
}
