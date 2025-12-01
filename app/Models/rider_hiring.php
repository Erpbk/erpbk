<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;

class rider_hiring extends Model
{
    use HasFactory, LogsActivity;
    protected $fillable = [
        'name',
        'contact',
        'whatsapp_contact',
        'fleet_sup',
        'stay',
        'nationality',
        'detail',
        'created_by',
        'updated_by',
    ];
}
