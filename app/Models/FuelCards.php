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
        'fuel_company_id',
        'status',
        'assigned_to',
        'created_by',
        'updated_by',
        'bike_no',
    ];

    protected $casts = [
        'card_number' => 'string',
        'fuel_company_id' => 'integer',
        'status' => 'string',
        'assigned_to' => 'integer',
        'created_by' => 'integer',
        'updated_by' => 'integer',
        'bike_no' => 'string',
        'attachment' => 'string',
    ];

    public static array $rules = [
        'card_number' => 'required|string|min:16',
        'fuel_company_id' => 'nullable|exists:fuel_companies,id',
        'status' => 'required|string|max:255',
        'assigned_to' => 'nullable|numeric',
        'created_by' => 'nullable|numeric',
        'updated_by' => 'nullable|numeric',
        'branch_id' => 'nullable|exists:branches,id',
    ];
    public function rider()
    {

        return $this->belongsTo(Riders::class, 'assigned_to', 'id');
    }

    public function histories()
    {
        return $this->hasMany(FuelCardHistory::class, 'card_id', 'id');
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

    /**
     * Find rider for specific date in card history
     */

    public function findRiderForDate($transactionDate)
    {

        $tripDate = $transactionDate ?? null;

        // Check card history for rider assigned on or before transaction date
        $history = $this->histories()
            ->whereDate('assign_date', '<=', $tripDate)
            ->where(function ($q) use ($tripDate) {
                $q->whereNull('return_date')
                    ->orWhereDate('return_date', '>=', $tripDate);
            })
            ->orderBy('assign_date', 'desc')
            ->first();

        if ($history && $history->assigned_to) {
            return Riders::find($history->assigned_to);
        }

        return null;
    }

    public function fuelCompany()
    {
        return $this->belongsTo(FuelCompany::class, 'fuel_company_id', 'id');
    }
}
