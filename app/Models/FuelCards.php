<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;

class FuelCards extends Model
{
    use LogsActivity;

    public $table = "fuel_cards";

    protected $fillable = [
        'card_number',
        'card_type',
        'status',
        'assigned_to',
        'created_by',
        'updated_by',
    ] ;

    protected $casts = [
        'card_number'=> 'string',
        'card_type'=> 'string',
        'status'=> 'string',
        'assigned_to'=> 'integer',
        'created_by'=> 'integer',
        'updated_by'=> 'integer',
    ];

    public static array $rules = [
        'card_number'=> 'required|string|min:16',
        'card_type'=> 'nullable|string|max:255',
        'status'=> 'required|string|max:255',
        'assigned_to'=> 'nullable|numeric',
        'created_by'=> 'nullable|numeric',
        'updated_by'=> 'nullable|numeric',
    ];
    public function rider(){

        return $this->belongsTo(Riders::class,'assigned_to','id');
    }

    public function histories(){
        return $this->hasMany(FuelCardHistory::class, 'card_id','id');
    }
}
