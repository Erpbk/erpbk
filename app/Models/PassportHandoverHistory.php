<?php

namespace App\Models;

use App\Traits\LogsActivity;

class PassportHandoverHistory extends BaseModel
{
    use LogsActivity;

    public const STATUS_ISSUED = 'issued';

    public const STATUS_RETURNED = 'returned';

    public $table = 'passport_handover_histories';

    public $fillable = [
        'branch_id',
        'rider_id',
        'employee_id',
        'holder_type',
        'holder_name',
        'passport_number',
        'handed_over_by',
        'received_by',
        'returned_by',
        'return_received_by',
        'note_date',
        'return_date',
        'remarks',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'note_date' => 'datetime',
        'return_date' => 'datetime',
        'remarks' => 'string',
        'status' => 'string',
        'holder_name' => 'string',
        'passport_number' => 'string',
        'handed_over_by' => 'string',
        'received_by' => 'string',
        'returned_by' => 'string',
        'return_received_by' => 'string',
    ];

    public function rider()
    {
        return $this->belongsTo(Riders::class, 'rider_id', 'id');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id', 'id');
    }

    public function createdByUser()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    public function updatedByUser()
    {
        return $this->belongsTo(User::class, 'updated_by', 'id');
    }

    public function personName(): string
    {
        if ($this->holder_type === 'employee' && $this->employee) {
            return $this->employee->name;
        }

        if ($this->rider) {
            return $this->rider->name;
        }

        return $this->holder_name ?? '-';
    }

    public function personCode(): string
    {
        if ($this->holder_type === 'employee' && $this->employee) {
            return $this->employee->employee_id ?? (string) $this->employee->id;
        }

        if ($this->rider) {
            return $this->rider->rider_id ?? (string) $this->rider->id;
        }

        return '-';
    }

    public function isOpen(): bool
    {
        return strtolower((string) $this->status) === self::STATUS_ISSUED;
    }
}
