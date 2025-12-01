<?php

namespace App\Repositories;

use App\Models\Dropdowns;
use App\Repositories\BaseRepository;

class DropdownsRepository extends BaseRepository
{
  protected $fieldSearchable = [
    'name',
    'label',
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

    Dropdowns::updateOrCreate(
      ['id' => $id],
      $input
    );
  }
}
