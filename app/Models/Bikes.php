<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\LogsActivity;
use App\Traits\BranchScope;

class Bikes extends BaseModel
{
  use SoftDeletes, LogsActivity, BranchScope;

  public $table = 'bikes';

  public $fillable = [
    'branch_id',
    'plate',
    'vehicle_type',
    'chassis_number',
    'color',
    'model',
    'model_type',
    'engine',
    'company',
    'bike_owner',
    'rider_id',
    'rental_company_id',
    'notes',
    'created_by',
    'updated_by',
    'warehouse',
    'traffic_file_number',
    'emirates',
    'bike_code',
    'registration_date',
    'expiry_date',
    'insurance_expiry',
    'status',
    'insurance_co',
    'customer_id',
    'bike_top_option_id',
    'contract_number',
    'policy_no',
    'current_km',
    'previous_km',
    'maintenance_km',
    'custom_field_values',
    'leased_return_by',
    'leased_return_date',
    'leased_return_company_id',
  ];

  protected $casts = [
    'branch_id' => 'integer',
    'plate' => 'string',
    'vehicle_type' => 'string',
    'chassis_number' => 'string',
    'color' => 'string',
    'model' => 'string',
    'model_type' => 'string',
    'engine' => 'string',
    'notes' => 'string',
    'warehouse' => 'string',
    'traffic_file_number' => 'string',
    'emirates' => 'string',
    'bike_code' => 'string',
    /*   'registration_date' => 'date',
      'expiry_date' => 'date',
      'insurance_expiry' => 'date', */
    'insurance_co' => 'string',
    'policy_no' => 'string',
    'customer_id' => 'string',
    'bike_top_option_id' => 'integer',
    'deleted_at' => 'datetime',
    'custom_field_values' => 'array',
    'leased_return_by' => 'date',
    'leased_return_date' => 'date',
    'leased_return_company_id' => 'integer',
    'bike_owner' => 'string',
  ];

  protected $dates = ['deleted_at'];

  public static array $rules = [
    'branch_id' => 'exists:branches,id',
    'plate' => 'nullable|string|max:100',
    'vehicle_type' => 'nullable|string|max:100',
    'chassis_number' => 'nullable|string|max:100',
    'color' => 'nullable|string|max:100',
    'model' => 'nullable|string|max:100',
    'model_type' => 'nullable|string|max:100',
    'engine' => 'nullable|string|max:100',
    'company' => 'nullable',
    'rider_id' => 'nullable',
    'notes' => 'nullable|string|max:65535',
    'created_by' => 'nullable',
    'updated_by' => 'nullable',
    'created_at' => 'nullable',
    'updated_at' => 'nullable',
    'warehouse' => 'nullable|string|max:50',
    'traffic_file_number' => 'nullable|string|max:100',
    'emirates' => 'nullable|string|max:100',
    'bike_code' => 'nullable|string|max:100',
    'rental_company_id' => 'nullable',
    'registration_date' => 'nullable',
    'expiry_date' => 'nullable',
    'insurance_expiry' => 'nullable',
    'insurance_co' => 'nullable|string|max:255',
    'policy_no' => 'nullable|string|max:100',
    'customer_id' => 'nullable|string|max:100',
    'leased_return_by' => 'nullable|date',
    'leased_return_date' => 'nullable|date',
    'leased_return_company_id' => 'nullable|integer|exists:leasing_companies,id',
    'bike_owner' => 'required|string|in:Owned,Leased',
  ];
  public static function dropdown()
  {
    return self::select('id', 'plate')->pluck('plate', 'id')->prepend('Select', '');
  }

  public function emiratesPlateLabel(): string
  {
    $emirates = trim((string) ($this->emirates ?? ''));
    $plate = trim((string) ($this->plate ?? ''));

    if ($emirates !== '' && $plate !== '') {
      return $emirates . '-' . $plate;
    }

    return $emirates !== '' ? $emirates : ($plate !== '' ? $plate : (string) ($this->bike_code ?? $this->id));
  }

  public static function availableForFixedAssetSelect(?int $exceptAssetId = null)
  {
    $assignedQuery = FixedAsset::query()
      ->whereNotNull('bike_id');

    if ($exceptAssetId) {
      $assignedQuery->where('id', '!=', $exceptAssetId);
    }

    $assignedBikeIds = $assignedQuery->pluck('bike_id');

    return self::query()
      ->when($assignedBikeIds->isNotEmpty(), fn ($query) => $query->whereNotIn('id', $assignedBikeIds))
      ->orderBy('emirates')
      ->orderBy('plate')
      ->get();
  }

  public static function availableForFixedAssetOptions(?int $exceptAssetId = null): array
  {
    return self::availableForFixedAssetSelect($exceptAssetId)
      ->mapWithKeys(fn (self $bike) => [$bike->id => $bike->emiratesPlateLabel()])
      ->all();
  }

  public static function riderBikes()
  {
    //return self::select('id', \DB::raw("CONCAT(plate, '-', company) as plate_name"))->whereNotNull('rider_id')->pluck('plate_name', 'id')->prepend('Select', '');
    return self::with('leasingCompany')
      ->whereNotNull('rider_id')
      ->get(['id', 'plate', 'company']) // You must fetch the foreign key too
      ->mapWithKeys(function ($item) {
        return [$item->id => $item->plate . ' - ' . ($item->leasingCompany->name ?? 'N/A')];
      })
      ->prepend('Select', '');
  }
  public function rider()
  {
    return $this->belongsTo(Riders::class, 'rider_id', 'id');
  }
  public function history()
  {
    return $this->hasMany(BikeHistory::class, 'bike_id', 'id');
  }
  public function LeasingCompany()
  {
    return $this->belongsTo(LeasingCompanies::class, 'company');
  }

  public function leasedReturnCompany()
  {
    return $this->belongsTo(LeasingCompanies::class, 'leased_return_company_id');
  }

  public function rentalCompany()
  {
    return $this->belongsTo(BikeRentCompany::class, 'rental_company_id', 'id');
  }

  public function customer()
  {
    return $this->belongsTo(Customers::class, 'customer_id');
  }

  public function branch()
  {
    return $this->belongsTo(Branch::class, 'branch_id', 'id');
  }

  /**
   * Leasing return: "return by" = target date, "return date" = completed return.
   *
   * @return array{label: string, badge: string}
   */
  public function leasedReturnDisplay(): array
  {
    if ($this->leased_return_date) {
      return ['label' => 'Returned', 'badge' => 'bg-label-success'];
    }
    if (!$this->leased_return_by) {
      return ['label' => 'Not set', 'badge' => 'bg-secondary'];
    }

    $due = \Carbon\Carbon::parse($this->leased_return_by)->startOfDay();
    $today = now()->startOfDay();

    if ($due->lt($today)) {
      return ['label' => 'Overdue', 'badge' => 'bg-label-danger'];
    }
    if ($due->lte($today->copy()->addDays(7))) {
      return ['label' => 'Due soon', 'badge' => 'bg-label-warning'];
    }

    return ['label' => 'Scheduled', 'badge' => 'bg-label-info'];
  }

  public function maintenanceStatus(): string
  {
    if ($this->current_km === null || $this->previous_km === null || $this->maintenance_km === null) {
      return 'missing_data';
    }

    $km = max(0, $this->current_km - $this->previous_km);
    if ($km > $this->maintenance_km) {
      return 'overdue';
    }
    if ($km >= ($this->maintenance_km * 0.8)) {
      return 'due';
    }
    return 'good';
  }

  public function scopeWithMaintenanceStats($query)
  {
    return $query->selectRaw("
        COUNT(*) as active,
        SUM(CASE WHEN current_km IS NULL OR previous_km IS NULL OR maintenance_km IS NULL THEN 1 ELSE 0 END) as missingData,
        SUM(CASE WHEN current_km - previous_km < maintenance_km * 0.8 THEN 1 ELSE 0 END) as good,
        SUM(CASE WHEN current_km - previous_km BETWEEN maintenance_km * 0.8 AND maintenance_km THEN 1 ELSE 0 END) as due,
        SUM(CASE WHEN current_km - previous_km > maintenance_km THEN 1 ELSE 0 END) as overdue
    ");
  }

  public function maintenanceRecords()
  {
    return $this->hasMany(BikeMaintenance::class, 'bike_id', 'id');
  }
}
