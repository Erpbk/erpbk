{!! Form::open(['route' => ['files.destroy', $id], 'method' => 'delete','id'=>'formajax']) !!}
@php
  $storagePath = \App\Http\Controllers\FilesController::storageRelativePath((object) [
    'type' => $type ?? null,
    'type_id' => $type_id ?? null,
    'file_name' => $file_name ?? null,
  ]);
  $fileViewPermissions = [
    'documents_view',
    'bikes_documents_view',
    'riders_documents_view',
    'employees_document_view',
    'customers_documents_view',
    'suppliers_documents_view',
    'leasing_companies_documents_view',
    'bike_on_rent_documents_view',
    'garages_documents_view',
    'cash_&_banks_banks_view',
  ];
  $fileDeletePermissions = [
    'documents_delete',
    'bikes_documents_delete',
    'riders_documents_delete',
    'employees_document_delete',
    'customers_documents_delete',
    'suppliers_documents_delete',
    'leasing_companies_documents_delete',
    'bike_on_rent_documents_delete',
    'garages_documents_delete',
    'cash_&_banks_banks_delete',
  ];
@endphp
<div class='btn-group'>
  @canany($fileViewPermissions)
    <a href="{{ storage_url($storagePath) }}" target="_blank" class='btn btn-default btn-sm'>
        <i class="fa fa-eye"></i>
    </a>
  @endcanany
  @canany($fileDeletePermissions)
    {!! Form::button('<i class="fa fa-trash"></i>', [
        'type' => 'submit',
        'class' => 'btn btn-danger btn-sm',
        'onclick' => 'return confirm("Are you sure you want to delete this?")'
    ]) !!}
  @endcanany
</div>
{!! Form::close() !!}
