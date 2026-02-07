<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\LogsActivity;

class Bikes extends Model
{
  use SoftDeletes, LogsActivity;

  public $table = 'bikes';

  public $fillable = [
    'plate',
    'vehicle_type',
    'chassis_number',
    'color',
    'model',
    'model_type',
    'engine',
    'company',
    'rider_id',
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
    'contract_number',
    'policy_no',
    'current_km',
    'previous_km',
    'maintenance_km',
  ];

  protected $casts = [
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
    'deleted_at' => 'datetime'
  ];

  protected $dates = ['deleted_at'];

  public static array $rules = [
    'plate' => 'required|string|max:100',
    'vehicle_type' => 'required|string|max:100',
    'chassis_number' => 'required|string|max:100',
    'color' => 'required|string|max:100',
    'model' => 'required|string|max:100',
    'model_type' => 'required|string|max:100',
    'engine' => 'required|string|max:100',
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
    'registration_date' => 'nullable',
    'expiry_date' => 'nullable',
    'insurance_expiry' => 'nullable',
    'insurance_co' => 'nullable|string|max:255',
    'policy_no' => 'nullable|string|max:100',
    'customer_id' => 'nullable|string|max:100'
  ];
  public static function dropdown()
  {
    return self::select('id', 'plate')->pluck('plate', 'id')->prepend('Select', '');
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
  public function LeasingCompany()
  {
    return $this->belongsTo(LeasingCompanies::class, 'company');
  }

  public function customer()
  {
    return $this->belongsTo(Customers::class, 'customer_id');
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

  public function maintenanceRecords(){
    return $this->hasMany(BikeMaintenance::class,'bike_id','id');
  }
}
