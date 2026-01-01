<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\LogsActivity;

class RiderItemPrice extends Model
{
  use HasFactory, LogsActivity, SoftDeletes;
  protected $guarded = [];


  public function item()
  {
    return $this->hasOne(Items::class, 'id', 'item_id');
  }
}
