<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;

class Countries extends BaseModel
{
  use HasFactory, LogsActivity;
  public $table = 'countries';

  public static function list()
  {
    return self::query()->orderBy('name')->pluck('name', 'id');
  }
  public static function phonecode()
  {
    return self::all()->pluck('code', 'id');
  }
}
