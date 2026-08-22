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
        'license_no',
        'license_expiry',
        'road_permit',
        'road_permit_expiry',
        'person_code',
        'labor_card_number',
        'labor_card_expiry',
        'wps',
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
        'license_expiry' => 'date',
        'road_permit_expiry' => 'date',
        'labor_card_expiry' => 'date',
        'emirate_expiry' => 'date',
        'passport_expiry' => 'date',
        'salary' => 'decimal:2',
        'custom_field_values' => 'array',
    ];

    /** Base validation rules; required/optional per field comes from Employee Settings assignments. */
    public static array $rules = [
        'employee_id' => 'nullable|string|max:191',
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
        'license_no' => 'nullable|string|max:191',
        'license_expiry' => 'nullable|date',
        'road_permit' => 'nullable|string|max:255',
        'road_permit_expiry' => 'nullable|date',
        'person_code' => 'nullable|string|max:50',
        'labor_card_number' => 'nullable|string|max:100',
        'labor_card_expiry' => 'nullable|date',
        'wps' => 'nullable|string|max:100',
        'status' => 'nullable|in:active,inactive,on_leave',
        'address' => 'nullable|string',
        'profile_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        'notes' => 'nullable|string',
    ];

    public function isAbsconded(): bool
    {
        return in_array(strtolower(trim((string) $this->status)), ['absconded', 'absconder'], true);
    }

    public function scopeWhereAbsconded($query)
    {
        return $query->whereRaw('LOWER(TRIM(COALESCE(status, ""))) IN (?, ?)', ['absconded', 'absconder']);
    }

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
        return $this->belongsTo(Accounts::class, 'account_id');
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
        return self::dropdownForBranch(null);
    }

    /**
     * Employees for SIM assignment (same branch as the SIM, active only).
     */
    public static function dropdownForBranch(?int $branchId): array
    {
        $query = self::query()->where('status', 'active');

        if ($branchId !== null && $branchId > 0) {
            $query->where(function ($q) use ($branchId) {
                $q->where('branch_id', $branchId)->orWhereNull('branch_id');
            });
        }

        return $query
            ->select('id', DB::raw("CONCAT(employee_id, '-', name) as full_name"))
            ->orderBy('name')
            ->pluck('full_name', 'id')
            ->prepend('Select', '')
            ->all();
    }

    /**
     * All employees for SIM assignment (any status, all branches).
     *
     * @return array<int|string, string>
     */
    public static function dropdownForSimAssign(): array
    {
        return self::query()
            ->select('id', DB::raw("CONCAT(employee_id, '-', name, CASE WHEN status = 'active' THEN '' ELSE CONCAT(' (', status, ')') END) as full_name"))
            ->orderBy('name')
            ->pluck('full_name', 'id')
            ->prepend('Select', '')
            ->all();
    }

    public function simHistories()
    {
        return $this->hasMany(SimHistory::class, 'employee_id', 'id');
    }

    public function histories()
    {
        return $this->hasMany(EmployeeHistory::class, 'employee_id', 'id')
            ->orderByDesc('effective_date')
            ->orderByDesc('id');
    }

    public function getProfileImageUrlAttribute(): ?string
    {
        if (empty($this->profile_image)) {
            return null;
        }

        $path = ltrim(str_replace('\\', '/', $this->profile_image), '/');

        // Use the current request host (not only APP_URL) so images work on Laravel Cloud domains.
        return url('/storage/' . $path);
    }
}
