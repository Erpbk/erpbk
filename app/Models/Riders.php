<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use App\Traits\LogsActivity;
use App\Traits\HasActiveStatus;
use App\Traits\BranchScope;

class Riders extends BaseModel
{
  use SoftDeletes, LogsActivity, HasActiveStatus, BranchScope;

  public $table = 'riders';

  public $fillable = [
    'branch_id',
    'company_id',
    'name',
    'rider_id',
    'account_id',
    'email',
    'nationality',
    'doj',
    'designation',
    'emirate_hub',
    'rider_status_option',
    'emirate_id',
    'emirate_exp',
    'passport',
    'passport_expiry',
    'ethnicity',
    'dob',
    'license_no',
    'license_expiry',
    'road_permit',
    'road_permit_expiry',
    'created_by',
    'updated_by',
    'status',
    'rider_status',
    'fleet_supervisor',
    'wps',
    'image_name',
    'person_code',
    'labor_card_number',
    'labor_card_expiry',
    'attendance',
    'customer_id',
    'rider_top_option_id',
    'recuriter',
    'recruiter_id',
    'deleted_by',
    'custom_field_values'
  ];

  protected $casts = [
    'custom_field_values' => 'array',
    'company_id' => 'integer',
    'name' => 'string',
    'email' => 'string',
    'doj' => 'string',
    'emirate_id' => 'string',
    'emirate_exp' => 'string',
    'passport' => 'string',
    'passport_expiry' => 'string',
    'ethnicity' => 'string',
    'dob' => 'string',
    'license_no' => 'string',
    'license_expiry' => 'string',
    'road_permit' => 'string',
    'road_permit_expiry' => 'string',
    'fleet_supervisor' => 'string',
    'wps' => 'string',
    'image_name' => 'string',
    'rider_status' => 'string',

    'person_code' => 'string',
    'labor_card_number' => 'string',
    'labor_card_expiry' => 'string',
    'attendance' => 'string',
    'rider_top_option_id' => 'integer',
    'recuriter' => 'string',
    'recruiter_id' => 'integer',
    'deleted_at' => 'datetime'
  ];

  /**
   * The attributes that should be included in the model's array form.
   *
   * @var array
   */
  protected $dates = ['deleted_at'];

  public static array $rules = [
    'company_id' => 'nullable|integer|exists:companies,id',
    'name' => 'nullable|string|max:191|unique:riders,name',
    'rider_id' => 'nullable|unique:riders,rider_id',
    'email' => 'nullable|string|max:191',
    'nationality' => 'nullable',
    'doj' => 'nullable',
    'emirate_id' => 'nullable|string|max:191',
    'emirate_exp' => 'nullable',
    'passport' => 'nullable|string|max:191|unique:riders,passport',
    'passport_expiry' => 'nullable',
    'ethnicity' => 'nullable|string|max:191',
    'dob' => 'nullable',
    'license_no' => 'nullable|string|max:191',
    'license_expiry' => 'nullable',
    'road_permit' => 'nullable|string|max:191',
    'road_permit_expiry' => 'nullable',
    'created_by' => 'nullable',
    'updated_by' => 'nullable',
    'created_at' => 'nullable',
    'updated_at' => 'nullable',
    'customer_id' => '',
    'rider_top_option_id' => 'nullable|integer|exists:rider_top_options,id',
    'status' => 'nullable',
    'fleet_supervisor' => 'nullable|string|max:50',
    'wps' => 'nullable|string|max:100',
    'image_name' => 'nullable|string|max:255',
    'person_code' => 'nullable|string|max:50',
    'labor_card_number' => 'nullable|string|max:100',
    'labor_card_expiry' => 'nullable',
    'recuriter' => 'nullable|string|max:255',
    'recruiter_id' => 'nullable|integer|exists:recruiters,id'
  ];

  /**
   * Human label + Bootstrap badge class for riders.status (bike assign/return lifecycle).
   * 1 = active on fleet, 3 = inactive/off bike, 4 = vacation hold, 5 = absconded (see BikesController::assignrider).
   *
   * @param  int|string|null  $status
   * @return array{label: string, badge: string}
   */
  public static function employmentStatusDisplay($status): array
  {
    $code = $status === null || $status === '' ? null : (int) $status;

    return match ($code) {
      1 => ['label' => 'Active', 'badge' => 'bg-label-success'],
      3 => ['label' => 'Inactive', 'badge' => 'bg-label-secondary'],
      4 => ['label' => 'Vacation', 'badge' => 'bg-label-warning'],
      5 => ['label' => 'Absconded', 'badge' => 'bg-label-danger'],
      default => [
        'label' => $code === null ? 'Not set' : 'Status ' . $code,
        'badge' => 'bg-label-secondary',
      ],
    };
  }

  public function scopeActive($query)
  {
    return $query->where('status', 1);
  }

  public function items()
  {
    return $this->hasMany(RiderItemPrice::class, 'RID', 'id');
  }

  public function branch()
  {
    return $this->belongsTo(Branch::class, 'branch_id', 'id');
  }
  public static function dropdown()
  {
    return self::select('id', DB::raw("CONCAT(rider_id, '-', name) as full_name"))->pluck('full_name', 'id')->prepend('Select', '');
    //return self::select('id', 'name')->pluck('name', 'id')->prepend('Select', '');


  }
  public function bikes()
  {
    return $this->hasOne(Bikes::class, 'rider_id', 'id');
  }

  public function histories()
  {
    return $this->hasMany(RiderHistory::class, 'rider_id', 'id')->orderByDesc('effective_date')->orderByDesc('id');
  }

  public function jobstatus()
  {
    return $this->hasOne(JobStatus::class, 'RID', 'id')->orderByDesc('id');
  }
  public function vendor()
  {
    return $this->hasOne(Vendors::class, 'id', 'VID');
  }
  public function customer()
  {
    return $this->hasOne(Customers::class, 'id', 'customer_id');
  }
  function account()
  {
    return $this->hasOne(Accounts::class, 'id', 'account_id');
  }
  function sim()
  {
    return $this->hasOne(Sims::class, 'id', 'assign_to');
  }
  function country()
  {
    return $this->hasOne(Countries::class, 'id', 'nationality');
  }

  function transactions()
  {
    return $this->hasMany(Transactions::class, 'account_id', 'account_id');
  }
  function activity()
  {
    return $this->hasMany(RiderActivities::class, 'rider_id', 'id')->where(DB::raw('DATE_FORMAT(date, "%Y-%m")'), '=', date('Y-m'));
  }

  function recruiter()
  {
    return $this->belongsTo(Recruiters::class, 'recruiter_id', 'id');
  }
}
