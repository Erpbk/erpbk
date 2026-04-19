<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VehicleModels extends BaseModel
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'name',
        'status',
    ];
}
