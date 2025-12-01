<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;

class Files extends Model
{
    use LogsActivity;

  public $table = 'files';

  public $fillable = [
    'name',
    'type',
    'type_id',
    'file_name',
    'expiry_date',
    'status',
    'notes',
    'file_type'
  ];

  protected $casts = [
    /* 'type' => 'boolean', */
    /*     'file_name' => 'string',
     */ 'expiry_date' => 'date',
    'status' => 'boolean',
    'notes' => 'string',
    'file_type' => 'string'
  ];

  public static array $rules = [
    'name' => 'required|string',
    'file_name' => 'required|file|mimes:jpg,jpeg,png,pdf,doc,docx|max:2048',
    'type_id' => 'required',
    /*     'file_name' => 'nullable|string|max:100',
     */ 'expiry_date' => 'nullable',
    'status' => 'nullable|boolean',
    'notes' => 'nullable|string|max:100',
    'created_at' => 'nullable',
    'updated_at' => 'nullable',
    'file_type' => 'nullable|string|max:50'
  ];
  public static function dropdown($id = 0)
  {
    $res = self::all();
    $list = '';
    foreach ($res as $file) {
      $list .= '<option ' . ($id == $file->id ? 'selected' : '') . ' value="' . $file->id . '">' . $file->file_name . '</option>';
    }
    return $list;
  }
  public function rider()
  {
    return $this->hasOne(Riders::class, 'id', 'type_id');
  }
  public function bike()
  {
    return $this->hasOne(Bikes::class, 'id', 'type_id');
  }

}
