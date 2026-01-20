<?php

namespace App\Models;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class FuelCardHistory extends Model
{
    use LogsActivity;

    public $table = "fuel_card_histories";

    protected $fillable = [
        'card_id',
        'assigned_by',
        'returned_by',
        'assigned_to',
        'assign_date',
        'return_date',
        'note',
    ] ;

    protected $casts = [
        'card_id'=> 'integer',
        'assigned_by'=> 'integer',
        'returned_by'=> 'integer',
        'assigned_to'=> 'integer',
        'assign_date'=> 'date',
        'return_date'=> 'date',
        'note'=> 'string',
    ];

    protected $dates = [
        'assign_date',
        'return_date',
    ];

    public function rider(){
        return $this->belongsTo(Riders::class,'assigned_to','id');
    }

    public function assignedBy(){
        return $this->belongsTo(User::class,'assigned_by','id');
    }

    public function returnedBy(){
        return $this->belongsTo(User::class,'returned_by','id');
    }
}