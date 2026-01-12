<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\LogsActivity;
use App\Traits\HasActiveStatus;

class Riders extends Model
{
  use SoftDeletes, LogsActivity, HasActiveStatus;

  public $table = 'riders';

  public $fillable = [
    'name',
    'rider_id',
    'courier_id',
    'account_id',
    'personal_contact',
    'company_contact',
    'personal_email',
    'email',
    'nationality',
    'NFDID',
    'cdm_deposit_id',
    'doj',
    'emirate_hub',
    'emirate_id',
    'emirate_exp',
    'mashreq_id',
    'passport',
    'passport_expiry',
    'PID',
    'DEPT',
    'ethnicity',
    'dob',
    'license_no',
    'license_expiry',
    'visa_status',
    'branded_plate_no',
    'vaccine_status',
    'attach_documents',
    'other_details',
    'created_by',
    'updated_by',
    'VID',
    'visa_sponsor',
    'visa_occupation',
    'status',
    'absconder',
    'flowup',
    'l_license',
    'TAID',
    'fleet_supervisor',
    'passport_handover',
    'noon_no',
    'wps',
    'c3_card',
    'contract',
    'designation',
    'image_name',
    'salary_model',
    'rider_reference',
    'job_status',
    'person_code',
    'labor_card_number',
    'labor_card_expiry',
    'insurance',
    'insurance_expiry',
    'policy_no',
    'shift',
    'vat',
    'attendance',
    'customer_id',
    'attendance_date',
    'recuriter',
    'recruiter_id',
    'deleted_by'
  ];

  protected $casts = [
    'name' => 'string',
    'courier_id' => 'string',
    'personal_contact' => 'string',
    'company_contact' => 'string',
    'personal_email' => 'string',
    'email' => 'string',
    'NFDID' => 'string',
    'cdm_deposit_id' => 'string',
    'doj' => 'string',
    'emirate_hub' => 'string',
    'emirate_id' => 'string',
    'emirate_exp' => 'string',
    'mashreq_id' => 'string',
    'passport' => 'string',
    'passport_expiry' => 'string',
    'DEPT' => 'string',
    'ethnicity' => 'string',
    'dob' => 'string',
    'license_no' => 'string',
    'license_expiry' => 'string',
    'visa_status' => 'string',
    'branded_plate_no' => 'string',
    'vaccine_status' => 'boolean',
    'attach_documents' => 'string',
    'other_details' => 'string',
    'visa_sponsor' => 'string',
    'visa_occupation' => 'string',
    'absconder' => 'boolean',
    'flowup' => 'boolean',
    'l_license' => 'boolean',
    'fleet_supervisor' => 'string',
    'passport_handover' => 'string',
    'noon_no' => 'string',
    'wps' => 'string',
    'c3_card' => 'string',
    'contract' => 'string',
    'designation' => 'string',
    'image_name' => 'string',
    'salary_model' => 'string',
    'rider_reference' => 'string',

    'person_code' => 'string',
    'labor_card_number' => 'string',
    'labor_card_expiry' => 'string',
    'insurance' => 'string',
    'insurance_expiry' => 'string',
    'shift' => 'string',
    'attendance' => 'string',
    'policy_no' => 'string',
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
    'name' => 'required|string|max:191|unique:riders,name',
    'rider_id' => 'required|unique:riders,rider_id',
    'courier_id' => 'nullable|string|max:191',
    'personal_contact' => 'nullable|string|max:191',
    'company_contact' => 'nullable|string|max:191',
    'personal_email' => 'required|string|max:191',
    'email' => 'nullable|string|max:191',
    'nationality' => 'required',
    'NFDID' => 'nullable|string|max:191',
    'cdm_deposit_id' => 'nullable|string|max:191',
    'doj' => 'required',
    'emirate_hub' => 'string|max:191',
    'emirate_id' => 'required|string|max:191',
    'emirate_exp' => 'required',
    'mashreq_id' => 'nullable|string|max:191',
    'passport' => 'required|string|max:191|unique:riders,passport',
    'passport_expiry' => 'required',
    'PID' => 'nullable',
    'DEPT' => 'nullable|string|max:191',
    'ethnicity' => 'nullable|string|max:191',
    'dob' => 'nullable',
    'license_no' => 'required|string|max:191',
    'license_expiry' => 'required',
    'visa_status' => 'nullable|string|max:191',
    'branded_plate_no' => 'nullable|string|max:191',
    'vaccine_status' => 'nullable|boolean',
    'attach_documents' => 'nullable|string',
    'other_details' => 'nullable|string|max:65535',
    'created_by' => 'nullable',
    'updated_by' => 'nullable',
    'created_at' => 'nullable',
    'updated_at' => 'nullable',
    'VID' => 'required',
    'customer_id' => '',
    'visa_sponsor' => 'nullable|string|max:100',
    'visa_occupation' => 'required|string|max:100',
    'status' => 'nullable',
    'TAID' => 'nullable',
    'fleet_supervisor' => 'required|string|max:50',
    'passport_handover' => 'required|string|max:50',
    'noon_no' => 'nullable|string|max:100',
    'wps' => 'nullable|string|max:100',
    'c3_card' => 'nullable|string|max:100',
    'contract' => 'nullable|string|max:100',
    'designation' => 'string|max:50',
    'image_name' => 'nullable|string|max:255',
    'salary_model' => 'required|string|max:100',
    'rider_reference' => 'required|string|max:255',
    'job_status' => 'nullable|boolean',
    'person_code' => 'nullable|string|max:50',
    'labor_card_number' => 'nullable|string|max:100',
    'labor_card_expiry' => 'nullable',
    'insurance' => 'nullable|string|max:100',
    'insurance_expiry' => 'nullable',
    'policy_no' => 'nullable|string|max:255',
    'recuriter' => 'nullable|string|max:255',
    'recruiter_id' => 'nullable|integer|exists:recruiters,id'
  ];

  public function items()
  {
    return $this->hasMany(RiderItemPrice::class, 'RID', 'id');
  }


  public static function dropdown()
  {
    return self::select('id', \DB::raw("CONCAT(rider_id, '-', name) as full_name"))->pluck('full_name', 'id')->prepend('Select', '');
    //return self::select('id', 'name')->pluck('name', 'id')->prepend('Select', '');


  }
  public function bikes()
  {
    return $this->hasOne(Bikes::class, 'rider_id', 'id');
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
    return $this->hasMany(RiderActivities::class, 'rider_id', 'id')->where(\DB::raw('DATE_FORMAT(date, "%Y-%m")'), '=', date('Y-m'));
  }

  function recruiter()
  {
    return $this->belongsTo(Recruiters::class, 'recruiter_id', 'id');
  }
}
