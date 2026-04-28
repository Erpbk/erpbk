<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;
use Illuminate\Support\Facades\Auth;

class Dropdowns extends BaseModel
{
  use LogsActivity;

  public $table = 'dropdowns';

  public $fillable = [
    'company_id',
    'name',
    'label',
    'values',
    'key',
    'status'
  ];

  protected $casts = [
    'name' => 'string',
    'label' => 'string',
    'company_id' => 'integer',
    'values' => 'string',
    'key' => 'string',
    'status' => 'boolean'
  ];

  public static array $rules = [
    'name' => 'nullable|string|max:255',
    'label' => 'nullable|string|max:255',
    'company_id' => 'nullable|integer',
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

  /**
   * Ensure dropdowns are always scoped to the current company,
   * even on routes that do not carry company_slug.
   */
  protected function resolveScopedCompanyId(): ?int
  {
    $companyId = parent::resolveScopedCompanyId();
    if ($companyId !== null) {
      return $companyId;
    }

    if (Auth::check() && !empty(Auth::user()->company_id)) {
      return (int) Auth::user()->company_id;
    }

    return null;
  }
}
