<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use App\Traits\BranchScope;

class Employee extends BaseModel
{
    use HasFactory, SoftDeletes, BranchScope;

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
        'custom_field_values',
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
        'custom_field_values' => 'array',
    ];

    /** Base validation rules; required/optional per field comes from Employee Settings assignments. */
    public static array $rules = [
        'employee_id' => 'nullable|string|max:191|unique:employees,employee_id',
        'name' => 'nullable|string|max:255',
        'company_email' => 'nullable|email|max:191|unique:employees,company_email',
        'personal_email' => 'nullable|email|max:191|unique:employees,personal_email',
        'company_contact' => 'nullable|string|max:20',
        'personal_contact' => 'nullable|string|max:20',
        'emergency_contact' => 'nullable|string|max:20',
        'nationality_id' => 'nullable|exists:countries,id',
        'department_id' => 'nullable|exists:departments,id',
        'designation' => 'nullable|string|max:255',
        'salary' => 'nullable|numeric|min:0',
        'branch_id' => 'nullable|exists:branches,id',
        'emirate_id' => 'nullable|string|max:191|unique:employees,emirate_id',
        'emirate_expiry' => 'nullable|date',
        'passport' => 'nullable|string|max:191|unique:employees,passport',
        'passport_expiry' => 'nullable|date',
        'doj' => 'nullable|date',
        'dob' => 'nullable|date|before:today',
        'visa_sponsor' => 'nullable|string|max:255',
        'visa_occupation' => 'nullable|string|max:255',
        'visa_expiry' => 'nullable|date',
        'status' => 'nullable|in:active,inactive,on_leave',
        'address' => 'nullable|string',
        'profile_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        'notes' => 'nullable|string',
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

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public static function dropdown()
    {
        return self::select('id', DB::raw("CONCAT(employee_id, '-', name) as full_name"))
            ->pluck('full_name', 'id')
            ->prepend('Select', '');
    }
}
