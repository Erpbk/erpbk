<?php

namespace App\DataTables;

use App\Models\UploadFile;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\EloquentDataTable;
use Illuminate\Support\Facades\Route;

class UploadFilesDataTable extends DataTable
{
    public function dataTable($query)
    {
        $dataTable = new EloquentDataTable($query);

        return $dataTable
            ->addColumn('name', function ($file) {
                return '<a href="' . route('upload_files.show', $file->id) . '">' . e($file->name) . '</a>';
            })
            ->addColumn('uploaded_by', function ($file) {
                return optional($file->uploader)->name;
            })
            ->editColumn('created_at', function ($file) {
                return optional($file->created_at)->format('d M Y, h:i A');
            })
            ->addColumn('action', function ($file) {
                return view('upload_files.datatables_actions', compact('file'))->render();
            })
            ->rawColumns(['name', 'action']); // Required for rendering HTML
    }

    public function query(UploadFile $model)
    {
        return $model->newQuery()->with('uploader');
    }

    public function html()
    {
        return $this->builder()
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->addAction(['width' => '120px', 'printable' => false])
            ->parameters([
                'dom' => 'Bfrtip',
                'stateSave' => true,
                'order' => [[0, 'desc']],
                'buttons' => [
                    // Optional: Enable buttons if needed
                    // ['extend' => 'create', 'className' => 'btn btn-default btn-sm'],
                    // ['extend' => 'export', 'className' => 'btn btn-default btn-sm'],
                    // ['extend' => 'print', 'className' => 'btn btn-default btn-sm'],
                ],
            ]);
    }

    protected function getColumns()
    {
        return [
            'name' => ['title' => 'File Name'],
            'detail' => ['title' => 'Details'],
            'uploaded_by' => ['title' => 'Uploaded By'],
            'created_at' => ['title' => 'Uploaded At'],
        ];
    }

    protected function filename(): string
    {
        return 'upload_files_' . time();
    }
}
