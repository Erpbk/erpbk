<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;

class Dropdowns extends Model
{
    use LogsActivity;

  public $table = 'dropdowns';

  public $fillable = [
    'name',
    'label',
    'values',
    'key',
    'status'
  ];

  protected $casts = [
    'name' => 'string',
    'label' => 'string',
    'values' => 'string',
    'key' => 'string',
    'status' => 'boolean'
  ];

  public static array $rules = [
    'name' => 'nullable|string|max:255',
    'label' => 'nullable|string|max:255',
    'key' => 'nullable|string|max:200|unique:dropdowns',
    'status' => 'nullable|boolean',
    'created_at' => 'nullable',
    'updated_at' => 'nullable'
  ];

  public static function list($key)
  {
    $dropdown = self::where('key', $key)->first();
    return json_decode($dropdown->values);
  }

}
