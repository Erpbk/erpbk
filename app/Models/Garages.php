<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;

class Garages extends Model
{
    use LogsActivity;

  public $table = 'garages';

  public $fillable = [
    'name',
    'contact_person',
    'address',
    'contact_number',
    'detail',
    'status',
    'parent_id',
    'account_id'
  ];

  protected $casts = [
    'name' => 'string',
    'contact_person' => 'string',
    'address' => 'string',
    'contact_number' => 'string',
    'detail' => 'string'
  ];

  public static array $rules = [
    'name' => 'nullable|string|max:255',
    'contact_person' => 'nullable|string|max:255',
    'address' => 'nullable|string|max:255',
    'contact_number' => 'nullable|string|max:100',
    'detail' => 'nullable|string|max:65535',
    'parent_id' => 'nullable|integer',
    'account_id' => 'nullable|integer',
    'created_at' => 'nullable',
    'updated_at' => 'nullable'
  ];

  /**
   * Relationship with Account model
   */
  public function account()
  {
    return $this->belongsTo(Accounts::class, 'account_id');
  }
}
