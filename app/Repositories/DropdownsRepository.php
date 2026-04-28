<?php

namespace App\Repositories;

use App\Models\Dropdowns;
use App\Repositories\BaseRepository;
use Illuminate\Support\Facades\Auth;

class DropdownsRepository extends BaseRepository
{
  protected $fieldSearchable = [
    'name',
    'label',
    'company_id',
    'values',
    'key',
    'status'
  ];

  public function getFieldsSearchable(): array
  {
    return $this->fieldSearchable;
  }

  public function model(): string
  {
    return Dropdowns::class;
  }


  public function save($request, $id = null)
  {
    $input = $request->all();
    $input['values'] = json_encode($input['values']);
    if (empty($input['company_id']) && Auth::check()) {
      $input['company_id'] = Auth::user()->company_id;
    }

    Dropdowns::updateOrCreate(
      ['id' => $id],
      $input
    );
  }
}
