<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\LogsActivity;
use App\Traits\BranchScope;

class Sims extends BaseModel
{
  use SoftDeletes, LogsActivity, BranchScope;

  public $table = 'sims';

  /** Taken out of service; cannot be assigned until reactivated. */
  public const STATUS_DEACTIVATED = 0;

  public const STATUS_ASSIGNED = 1;

  /** Held by the office, available to assign. */
  public const STATUS_IN_OFFICE = 2;

  /** Lost / not returned; charged via Inventory Loss and cannot be assigned. */
  public const STATUS_LOST = 3;

  public $fillable = [
    'branch_id',
    'number',
    'company',
    'assign_to',
    'assign_type',
    'created_by',
    'updated_by',
    'deleted_at',
    'fleet_supervisor',
    'status',
    'emi',
    'vendor',
    'lost_date',
    'lost_rider_id',
    'lost_employee_id',
    'lost_amount',
    'lost_voucher_id',
    'lost_trans_code',
    'lost_remarks',
    'lost_by',
  ];

  protected $casts = [
    'number' => 'string',
    'company' => 'string',
    'fleet_supervisor' => 'string',
    'emi' => 'string',
    'vendor' => 'string',
    'lost_date' => 'date',
    'lost_rider_id' => 'integer',
    'lost_employee_id' => 'integer',
    'lost_amount' => 'decimal:2',
    'lost_voucher_id' => 'integer',
    'lost_trans_code' => 'string',
    'lost_remarks' => 'string',
    'lost_by' => 'integer',
    'created_at' => 'datetime',
    'updated_at' => 'datetime',
    'deleted_at' => 'datetime'
  ];

  protected $dates = ['deleted_at'];

  public static array $rules = [
    'number' => 'nullable|string|min:10|max:13|unique:sims,number',
    'company' => 'nullable|exists:sim_companies,id',
    'vendor' => 'nullable|exists:customers,id',
    'branch_id' => 'nullable|numeric|exists:branches,id',
    'assign_to' => 'nullable',
    'created_by' => 'nullable',
    'updated_by' => 'nullable',
    'created_at' => 'nullable',
    'updated_at' => 'nullable',
    'deleted_at' => 'nullable',
    'fleet_supervisor' => 'nullable|string|max:50',
    'emi' => 'nullable|string|min:15|max:25',
  ];

  public function histories()
  {
    return $this->hasMany(SimHistory::class, 'sim_id', 'id');
  }

  public function riders()
  {
    return $this->belongsTo(Riders::class, 'assign_to', 'id');
  }

  public function employee()
  {
    return $this->belongsTo(Employee::class, 'assign_to', 'id');
  }

  public function assignee()
  {
    return $this->assign_type === 'employee' ? $this->employee() : $this->riders();
  }

  public function vendors()
  {
    return $this->hasOne(Customers::class, 'id', 'vendor');
  }

  public function telecomCompany()
  {
    return $this->belongsTo(SimCompany::class, 'company', 'id');
  }

  public function branch()
  {
    return $this->belongsTo(Branch::class, 'branch_id', 'id');
  }

  /**
   * Person currently holding this SIM (rider or employee).
   */
  public function assignedPerson()
  {
    if (!$this->assign_to) {
      return null;
    }

    return $this->assign_type === 'employee' ? $this->employee : $this->riders;
  }

  public function assigneeIsAbsconded(): bool
  {
    $person = $this->assignedPerson();
    if (!$person || !method_exists($person, 'isAbsconded')) {
      return false;
    }

    return $person->isAbsconded();
  }

  /**
   * SIMs whose assigned rider or employee is currently absconded.
   */
  public function scopeWhereAssigneeAbsconded($query)
  {
    return $query->whereNotNull('assign_to')->where(function ($q) {
      $q->where(function ($riderQ) {
        $riderQ->where(function ($type) {
          $type->whereNull('assign_type')
            ->orWhere('assign_type', '<>', 'employee');
        })->whereHas('riders', function ($riders) {
          $riders->whereAbsconded();
        });
      })->orWhere(function ($empQ) {
        $empQ->where('assign_type', 'employee')
          ->whereHas('employee', function ($employees) {
            $employees->whereAbsconded();
          });
      });
    });
  }

  /**
   * @return array{label: string, badge: string}
   */
  public static function statusDisplay(mixed $status): array
  {
    if ($status === null || $status === '') {
      return ['label' => 'Unknown', 'badge' => 'bg-secondary'];
    }

    return match ((int) $status) {
      self::STATUS_ASSIGNED => ['label' => 'Assigned', 'badge' => 'bg-success'],
      self::STATUS_IN_OFFICE => ['label' => 'In office', 'badge' => 'bg-info'],
      self::STATUS_DEACTIVATED => ['label' => 'Deactivated', 'badge' => 'bg-danger'],
      self::STATUS_LOST => ['label' => 'Lost', 'badge' => 'bg-dark'],
      default => ['label' => 'Unknown', 'badge' => 'bg-secondary'],
    };
  }

  public function isDeactivated(): bool
  {
    return (int) $this->status === self::STATUS_DEACTIVATED;
  }

  public function isLost(): bool
  {
    return (int) $this->status === self::STATUS_LOST;
  }

  /**
   * A SIM can only go to a rider or employee when it is sitting in the office.
   */
  public function isAssignable(): bool
  {
    return !$this->assign_to && !$this->isDeactivated() && !$this->isLost();
  }

  /**
   * Person who should be charged if this SIM is lost: the current holder, or the
   * most recent one when the SIM was already returned / left unreturned.
   *
   * @return array{type: string, model: Riders|Employee}|null
   */
  public function chargeablePerson(): ?array
  {
    if ($this->assign_to) {
      $person = $this->assignedPerson();
      if (!$person) {
        return null;
      }

      return [
        'type' => $this->assign_type === 'employee' ? 'employee' : 'rider',
        'model' => $person,
      ];
    }

    $lastHistory = $this->histories()
      ->where(function ($q) {
        $q->whereNotNull('rider_id')->orWhereNotNull('employee_id');
      })
      ->orderByDesc('note_date')
      ->orderByDesc('id')
      ->first();

    if (!$lastHistory) {
      return null;
    }

    if ($lastHistory->employee_id) {
      $employee = Employee::find($lastHistory->employee_id);
      return $employee ? ['type' => 'employee', 'model' => $employee] : null;
    }

    if ($lastHistory->rider_id) {
      $rider = Riders::find($lastHistory->rider_id);
      return $rider ? ['type' => 'rider', 'model' => $rider] : null;
    }

    return null;
  }

  public function lostRider()
  {
    return $this->belongsTo(Riders::class, 'lost_rider_id', 'id');
  }

  public function lostEmployee()
  {
    return $this->belongsTo(Employee::class, 'lost_employee_id', 'id');
  }

  public function lostBy()
  {
    return $this->belongsTo(User::class, 'lost_by', 'id');
  }

  public function lostVoucher()
  {
    return $this->belongsTo(Vouchers::class, 'lost_voucher_id', 'id');
  }

  public function lostVoucherLabel(): ?string
  {
    if (!$this->lost_voucher_id) {
      return null;
    }

    return 'IL-' . str_pad((string) $this->lost_voucher_id, 4, '0', STR_PAD_LEFT);
  }

  public function lostPersonLabel(): string
  {
    if ($this->lostEmployee) {
      $code = $this->lostEmployee->employee_id ?? $this->lostEmployee->id;
      return trim(($this->lostEmployee->name ?? 'Employee') . ' (' . $code . ')');
    }

    if ($this->lostRider) {
      $code = $this->lostRider->rider_id ?? $this->lostRider->id;
      return trim(($this->lostRider->name ?? 'Rider') . ' (' . $code . ')');
    }

    return 'holder';
  }

  public function createdBy()
  {
    return $this->belongsTo(User::class, 'created_by', 'id');
  }

  public function updatedBy()
  {
    return $this->belongsTo(User::class, 'updated_by', 'id');
  }

  public function invoiceItems()
  {
    return $this->hasMany(SimInvoiceItem::class, 'sim_id', 'id');
  }
}
