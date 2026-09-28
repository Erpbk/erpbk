<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\LogsActivity;
use App\Traits\HasActiveStatus;
use App\Traits\BranchScope;

class Customers extends BaseModel
{
  use LogsActivity, HasActiveStatus, SoftDeletes, BranchScope;

  public $table = 'customers';

  public const DEFAULT_CUSTOMER_NOTE_LABEL = 'Customer Note';

  public const DEFAULT_TERMS_AND_CONDITIONS_LABEL = 'Terms & Conditions';

  public $fillable = [
    'branch_id',
    'name',
    'company_name',
    'company_email',
    'contact_number',
    'address',
    'tax_number',
    'status',
    'tax_percentage',
    'customer_note',
    'customer_note_label',
    'terms_and_conditions',
    'terms_and_conditions_label',
  ];

  protected $casts = [
    'name' => 'string',
    'company_name' => 'string',
    'company_email' => 'string',
    'contact_number' => 'string',
    'address' => 'string',
    'tax_number' => 'string',
    'tax_percentage' => 'decimal:2',
    'customer_note' => 'string',
    'customer_note_label' => 'string',
    'terms_and_conditions' => 'string',
    'terms_and_conditions_label' => 'string',
  ];

  protected $dates = ['deleted_at'];

  public static array $rules = [
    'name' => 'required|string|max:255',
    'company_name' => 'nullable|string|max:255',
    'company_email' => 'nullable|string|max:100',
    'contact_number' => 'required|string|max:100',
    'address' => 'nullable|string|max:200',
    'tax_number' => 'required|string|max:100',

    'created_at' => 'nullable',
    'updated_at' => 'nullable',
    'tax_percentage' => 'required|numeric',
    'customer_note' => 'nullable|string',
    'customer_note_label' => 'nullable|string|max:100',
    'terms_and_conditions' => 'nullable|string',
    'terms_and_conditions_label' => 'nullable|string|max:100',
  ];

  public function resolvedCustomerNoteLabel(): string
  {
    $label = trim((string) ($this->customer_note_label ?? ''));

    return $label !== '' ? $label : self::DEFAULT_CUSTOMER_NOTE_LABEL;
  }

  public function resolvedTermsAndConditionsLabel(): string
  {
    $label = trim((string) ($this->terms_and_conditions_label ?? ''));

    return $label !== '' ? $label : self::DEFAULT_TERMS_AND_CONDITIONS_LABEL;
  }


  function account()
  {
    return $this->hasOne(Accounts::class, 'id', 'account_id');
  }

  function transactions()
  {
    return $this->hasMany(Transactions::class, 'account_id', 'account_id');
  }

  public static function dropdown()
  {
    $query = self::select('id', 'name')->pluck('name', 'id')->prepend('Select', '');
    return $query;
  }

  public function scopeActive($query)
  {
    return $query->where('status', 1);
  }

  public function invoices()
  {
    return $this->hasMany(CustomerInvoices::class, 'customer_id', 'id');
  }

  public function branch()
  {
      return $this->belongsTo(Branch::class, 'branch_id' , 'id');
  }

  public function getBranchNameAttribute()
  {
      $branch = $this->branch_id ? $this->branch->name .' ( '. $this->branch->code .' )' : 'All' ; 
      return $branch;
  }
}
