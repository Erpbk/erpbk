<?php

namespace App\DataTables;

use App\Http\Controllers\FilesController;
use App\Models\Files;
use App\Support\DocumentExpiryDashboard;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\EloquentDataTable;

class FilesDataTable extends DataTable
{
  /**
   * Build DataTable class.
   *
   * @param mixed $query Results from query() method.
   * @return \Yajra\DataTables\DataTableAbstract
   */
  public function dataTable($query)
  {
    $dataTable = new EloquentDataTable($query);
    $isGeneralDocuments = $this->isGeneralDocuments();

    $dataTable->editColumn('name', function (Files $file) {
      $label = e((string) ($file->name ?: $file->file_name ?: __('Document')));
      $type = trim((string) ($file->type ?? ''));
      $typeId = (int) ($file->type_id ?? 0);

      if ($type !== '' && $typeId > 0) {
        $url = DocumentExpiryDashboard::resourceUrlForFile($type, $typeId);
        if ($url) {
          return '<a href="' . e($url) . '" target="_blank" rel="noopener noreferrer">' . $label . '</a>';
        }
      }

      $storagePath = FilesController::storageRelativePath($file);
      $fileUrl = storage_url($storagePath);
      if ($fileUrl) {
        return '<a href="' . e($fileUrl) . '" target="_blank" rel="noopener noreferrer">' . $label . '</a>';
      }

      return $label;
    });

    if ($isGeneralDocuments) {
      $dataTable
        ->addColumn('module', function (Files $file) {
          $type = trim((string) ($file->type ?? ''));
          if ($type === '') {
            return __('Documents');
          }
          $config = DocumentExpiryDashboard::typeConfig()[$type] ?? null;

          return e((string) ($config['label'] ?? ucwords(str_replace('_', ' ', $type))));
        })
        ->editColumn('expiry_date', function (Files $file) {
          return $file->expiry_date
            ? $file->expiry_date->format('d M Y')
            : '—';
        });
    }

    return $dataTable
      ->addColumn('action', 'files.datatables_actions')
      ->rawColumns(['name', 'action']);
  }

  /**
   * Get query source of dataTable.
   *
   * @param \App\Models\Files $model
   * @return \Illuminate\Database\Eloquent\Builder
   */
  public function query(Files $model)
  {
    $query = $model->newQuery();

    // Documents module index: only general uploads (no linked resource).
    if ($this->isGeneralDocuments()) {
      return $query
        ->where(function ($q) {
          $q->whereNull('type')->orWhere('type', '');
        })
        ->where(function ($q) {
          $q->whereNull('type_id')->orWhere('type_id', 0);
        });
    }

    $type = $this->resolvedType();
    $typeId = $this->resolvedTypeId();

    $query->where('type', $type);
    if ($typeId > 0) {
      $query->where('type_id', $typeId);
    }

    return $query;
  }

  /**
   * Optional method if you want to use html builder.
   *
   * @return \Yajra\DataTables\Html\Builder
   */
  public function html()
  {
    $ajaxUrl = url()->current();
    if ($this->isGeneralDocuments()) {
      $ajaxUrl .= (str_contains($ajaxUrl, '?') ? '&' : '?') . 'documents=1';
    }

    return $this->builder()
      ->columns($this->getColumns())
      ->minifiedAjax($ajaxUrl)
      ->addAction(['width' => '120px', 'printable' => false])
      ->parameters([
        'dom' => 'Bfrtip',
        'stateSave' => false,
        'order' => [[0, 'desc']],
        'buttons' => [],
        'language' => [
          'processing' => '<div class="loading-overlay"><div class="spinner-border text-primary" role="status"></div></div>'
        ],
      ]);
  }

  /**
   * Get columns.
   *
   * @return array
   */
  protected function getColumns()
  {
    if ($this->isGeneralDocuments()) {
      return [
        'name' => ['title' => __('File Name')],
        'module' => ['title' => __('Module'), 'orderable' => false, 'searchable' => false],
        'expiry_date' => ['title' => __('Expiry Date')],
      ];
    }

    return [
      'name',
    ];
  }

  protected function isGeneralDocuments(): bool
  {
    if (request()->query('documents') === '1' || request()->query('documents') === 1) {
      return true;
    }

    return $this->resolvedType() === '';
  }

  protected function resolvedType(): string
  {
    return trim((string) (request()->query('type', '') ?: ''));
  }

  protected function resolvedTypeId(): int
  {
    return (int) request()->query('type_id', 0);
  }

  /**
   * Get filename for export.
   *
   * @return string
   */
  protected function filename(): string
  {
    return 'files_datatable_' . time();
  }
}
