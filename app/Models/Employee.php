<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'employee_id',
        'name',
        'company_email',
        'personal_email',
        'personal_contact',
        'company_contact',
        'emergency_contact',
        'nationality_id',
        'department_id',
        'designation',
        'salary',
        'branch_id',
        'emirate_id',
        'emirate_expiry',
        'passport',
        'passport_expiry',
        'doj',
        'status',
        'address',
        'dob',
        'visa_sponsor',
        'visa_occupation',
        'visa_expiry',
        'account_id',
        'profile_image',
        'notes',
        'created_by',
        'updated_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'doj' => 'date',
        'dob' => 'date',
        'visa_expiry' => 'date',
        'emirate_expiry' => 'date',
        'passport_expiry' => 'date',
        'salary' => 'decimal:2',
    ];

    /**
     * Scope a query to only include active employees.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeByBranch($query, $branchId)
    {
        return $query->where('branch_id', $branchId);
    }
    public function department()
    {
        return $this->belongsTo(Departments::class, 'department_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }
    public function nationality()
    {
        return $this->belongsTo(Countries::class, 'nationality_id');
    }

    public function account()
    {
        return $this->hasOne(Accounts::class, 'account_id');
    }
}