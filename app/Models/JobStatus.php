<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;

class JobStatus extends Model
{
  use HasFactory, LogsActivity;
  protected $guarded = [];
  public $table = 'job_status';

  public function rider()
  {
    return $this->hasOne(Riders::class, 'id', 'RID');
  }

  public function user()
  {
    return $this->hasOne(User::class, 'id', 'status_by');
  }

  public static function dropdown($id = 0)
  {
    $res = self::all();
    $list = '';
    foreach ($res as $rider) {
      $list .= '<option ' . ($id == $rider->id ? 'selected' : '') . ' value="' . $rider->id . '">' . $rider->plate . '</option>';
    }
    return $list;
  }
}
