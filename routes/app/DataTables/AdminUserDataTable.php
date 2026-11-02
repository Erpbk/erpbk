<?php

namespace App\DataTables;

use App\Models\AdminUser;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Services\DataTable;

class AdminUserDataTable extends DataTable
{
    public function dataTable($query)
    {
        $dataTable = new EloquentDataTable($query);

        $dataTable
            ->addColumn('role', function (AdminUser $user) {
                $role = $user->roles->pluck('name')->first();
                return '<span class="badge bg-label-success">' . e($role ?: 'No role') . '</span>';
            })
            ->addColumn('action', function (AdminUser $user) {
                return view('admin.users.datatables_actions', ['id' => $user->id])->render();
            });

        $dataTable->rawColumns(['role', 'action']);

        return $dataTable;
    }

    public function query(AdminUser $model)
    {
        return $model->newQuery()
            ->with('roles')
            ->orderByDesc('id');
    }

    public function html()
    {
        return $this->builder()
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->addAction(['width' => '180px', 'printable' => false])
            ->parameters([
                'dom' => 'Bfrtip',
                'stateSave' => true,
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
            'name' => ['title' => 'Name'],
            'email' => ['title' => 'Email'],
            'role' => ['title' => 'Role', 'orderable' => false, 'searchable' => false],
        ];
    }

    protected function filename(): string
    {
        return 'admin_users_datatable_' . time();
    }
}

