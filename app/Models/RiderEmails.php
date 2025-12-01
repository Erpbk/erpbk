<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;

class RiderEmails extends Model
{
    use LogsActivity;

  public $table = 'rider_emails';

  public $fillable = [
    'rider_id',
    'mail_to',
    'subject',
    'message',
    'status'
  ];

  protected $casts = [
    'mail_to' => 'string',
    'subject' => 'string',
    'message' => 'string',
    'status' => 'string'
  ];

  public static array $rules = [
    'rider_id' => 'nullable',
    'mail_to' => 'nullable|string|max:255',
    'subject' => 'nullable|string|max:255',
    'message' => 'nullable|string|max:65535',
    'created_at' => 'nullable',
    'updated_at' => 'nullable',
    'status' => 'nullable|string|max:20'
  ];

  public function rider()
  {
    //return $this->belongsTo(Riders::class);
    return $this->hasOne(Riders::class, 'id', 'rider_id');
  }
}
