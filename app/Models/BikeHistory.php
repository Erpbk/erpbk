<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;

class BikeHistory extends BaseModel
{
    use LogsActivity;

  public $table = 'bike_histories';

  public $fillable = [
    'bike_id',
    'branch_id',
    'rider_id',
    'rental_company_id',
    'notes',
    'note_date',
    'return_date',
    'created_by',
    'updated_by',
    'warehouse',
    'contract',
    'condition_attachments',
    'customer_id',
    'fleet_supervisor',
    'bike_number',
    'history_status',
  ];

  protected $casts = [
    'notes' => 'string',
    'note_date' => 'date',
    'return_date' => 'date',
    'warehouse' => 'string',
    'created_by' => 'string',
    'updated_by' => 'string',
    'contract' => 'string',
    'condition_attachments' => 'array',
  ];

  public function conditionAttachment(string $kind): ?string
  {
    $attachments = is_array($this->condition_attachments) ? $this->condition_attachments : [];
    $path = $attachments[$kind] ?? null;

    return is_string($path) && $path !== '' ? $path : null;
  }

  public function conditionAttachmentUrl(string $kind): ?string
  {
    return storage_url($this->conditionAttachment($kind));
  }

  public static array $rules = [
    'bike_id' => 'required',
    'rider_id' => 'nullable',
    'notes' => 'nullable|string|max:65535',
    'created_at' => 'nullable',
    'updated_at' => 'nullable',
    'note_date' => 'nullable',
    'return_date' => 'nullable',
    'created_by' => 'nullable',
    'updated_by' => 'nullable',
    'warehouse' => 'nullable|string|max:50',
    'contract' => 'nullable|string|max:255'
  ];

  public function rider()
  {
    return $this->belongsTo(Riders::class, 'rider_id', 'id');
  }
  public function bike()
  {
    return $this->belongsTo(Bikes::class, 'bike_id', 'id');
  }

  public function rentalCompany()
  {
    return $this->belongsTo(BikeRentCompany::class, 'rental_company_id', 'id');
  }

  public function branch()
  {
    return $this->belongsTo(Branch::class, 'branch_id', 'id');
  }
}
