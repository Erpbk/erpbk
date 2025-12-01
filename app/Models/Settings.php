<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;

class Settings extends Model
{
    use HasFactory, LogsActivity;
    protected $table = "settings";
    protected $fillable = [
        'name',
        'value'
    ];
}
