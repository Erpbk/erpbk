<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;
use App\Traits\BranchScope;

class FuelCards extends BaseModel
{
    use LogsActivity, BranchScope;

    public $table = "fuel_cards";

    protected $fillable = [
        'branch_id',
        'card_number',
        'card_type',
        'status',
        'assigned_to',
        'created_by',
        'updated_by',
        'bike_no',
    ] ;

    protected $casts = [
        'card_number'=> 'string',
        'card_type'=> 'string',
        'status'=> 'string',
        'assigned_to'=> 'integer',
        'created_by'=> 'integer',
        'updated_by'=> 'integer',
        'bike_no'=> 'string',
        'attachment'=> 'string',
    ];

    public static array $rules = [
        'card_number'=> 'required|string|min:16',
        'card_type'=> 'nullable|string|max:255',
        'status'=> 'required|string|max:255',
        'assigned_to'=> 'nullable|numeric',
        'created_by'=> 'nullable|numeric',
        'updated_by'=> 'nullable|numeric',
        'branch_id' => 'nullable|exists:branches,id',
    ];
    public function rider(){

        return $this->belongsTo(Riders::class,'assigned_to','id');
    }

    public function histories(){
        return $this->hasMany(FuelCardHistory::class, 'card_id','id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id', 'id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by', 'id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'Active');
    }
}
