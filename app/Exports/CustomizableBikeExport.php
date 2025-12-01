<?php

namespace App\Exports;

use App\Models\Bikes;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CustomizableBikeExport implements FromCollection, WithHeadings, WithMapping
{
    protected $visibleColumns;
    protected $columnOrder;
    protected $filters;

    // Define all available columns with their keys, titles, and data mappings
    protected $availableColumns = [
        'bike_code' => [
            'title' => 'Bike Code',
            'data' => 'bike_code'
        ],
        'plate' => [
            'title' => 'Plate Number',
            'data' => 'plate'
        ],
        'rider_name' => [
            'title' => 'Rider Name',
            'data' => 'rider_name'
        ],
        'rider_id' => [
            'title' => 'Rider ID',
            'data' => 'rider_id'
        ],
        'emirates' => [
            'title' => 'Emirates',
            'data' => 'emirates'
        ],
        'company' => [
            'title' => 'Company',
            'data' => 'company'
        ],
        'customer_id' => [
            'title' => 'Customer',
            'data' => 'customer_id'
        ],
        'expiry_date' => [
            'title' => 'Expiry Date',
            'data' => 'expiry_date'
        ],
        'warehouse' => [
            'title' => 'Warehouse',
            'data' => 'warehouse'
        ],
        'status' => [
            'title' => 'Status',
            'data' => 'status'
        ],
        'created_by' => [
            'title' => 'Created By',
            'data' => 'created_by'
        ],
        'updated_by' => [
            'title' => 'Updated By',
            'data' => 'updated_by'
        ],
        'chassis_number' => [
            'title' => 'Chassis Number',
            'data' => 'chassis_number'
        ],
        'color' => [
            'title' => 'Color',
            'data' => 'color'
        ],
        'model' => [
            'title' => 'Model',
            'data' => 'model'
        ],
        'model_type' => [
            'title' => 'Model Type',
            'data' => 'model_type'
        ],
        'engine' => [
            'title' => 'Engine',
            'data' => 'engine'
        ],
        'registration_date' => [
            'title' => 'Registration Date',
            'data' => 'registration_date'
        ],
        'insurance_expiry' => [
            'title' => 'Insurance Expiry',
            'data' => 'insurance_expiry'
        ],
        'insurance_co' => [
            'title' => 'Insurance Company',
            'data' => 'insurance_co'
        ],
        'policy_no' => [
            'title' => 'Policy Number',
            'data' => 'policy_no'
        ],
        'contract_number' => [
            'title' => 'Contract Number',
            'data' => 'contract_number'
        ],
        'traffic_file_number' => [
            'title' => 'Traffic File Number',
            'data' => 'traffic_file_number'
        ]
    ];

    public function __construct($visibleColumns = null, $columnOrder = null, $filters = [])
    {
        // If no columns specified, use all available columns
        $this->visibleColumns = $visibleColumns ?: array_keys($this->availableColumns);

        // If no order specified, use the original order
        $this->columnOrder = $columnOrder ?: array_keys($this->availableColumns);

        // Apply any filters
        $this->filters = $filters;
    }

    public function collection()
    {
        $query = Bikes::query();

        // Apply any filters that might be passed
        if (!empty($this->filters)) {
            foreach ($this->filters as $key => $value) {
                if (!empty($value)) {
                    switch ($key) {
                        case 'bike_code':
                            $query->where('bike_code', 'like', "%{$value}%");
                            break;
                        case 'plate':
                            $query->where('plate', 'like', "%{$value}%");
                            break;
                        case 'rider_id':
                            $query->where('rider_id', $value);
                            break;
                        case 'rider':
                            $query->where('rider_id', $value);
                            break;
                        case 'company':
                            $query->where('company', $value);
                            break;
                        case 'emirates':
                            $query->where('emirates', $value);
                            break;
                        case 'warehouse':
                            $query->where('warehouse', $value);
                            break;
                        case 'status':
                            $query->where('status', $value);
                            break;
                        case 'expiry_date_from':
                            $query->where('expiry_date', '>=', $value);
                            break;
                        case 'expiry_date_to':
                            $query->where('expiry_date', '<=', $value);
                            break;
                        case 'quick_search':
                            $query->leftJoin('riders', 'bikes.rider_id', '=', 'riders.id')
                                ->leftJoin('leasing_companies', 'bikes.company', '=', 'leasing_companies.id')
                                ->leftJoin('customers', 'bikes.customer_id', '=', 'customers.id')
                                ->where(function ($q) use ($value) {
                                    $q->where('bikes.plate', 'like', "%{$value}%")
                                        ->orWhere('bikes.bike_code', 'like', "%{$value}%")
                                        ->orWhere('bikes.chassis_number', 'like', "%{$value}%")
                                        ->orWhere('bikes.color', 'like', "%{$value}%")
                                        ->orWhere('bikes.model', 'like', "%{$value}%")
                                        ->orWhere('bikes.emirates', 'like', "%{$value}%")
                                        ->orWhere('bikes.warehouse', 'like', "%{$value}%")
                                        ->orWhere('riders.name', 'like', "%{$value}%")
                                        ->orWhere('riders.rider_id', 'like', "%{$value}%")
                                        ->orWhere('leasing_companies.name', 'like', "%{$value}%")
                                        ->orWhere('customers.name', 'like', "%{$value}%");
                                });
                            $query->select('bikes.*');
                            break;
                    }
                }
            }
        }

        return $query->orderBy('bike_code', 'desc')->get();
    }

    public function map($bike): array
    {
        $data = [];

        // Map each column according to the specified order
        foreach ($this->columnOrder as $columnKey) {
            // Only include visible columns
            if (in_array($columnKey, $this->visibleColumns)) {
                $data[] = $this->getColumnValue($bike, $columnKey);
            }
        }

        return $data;
    }

    public function headings(): array
    {
        $headings = [];

        // Generate headings according to the specified order
        foreach ($this->columnOrder as $columnKey) {
            // Only include visible columns
            if (in_array($columnKey, $this->visibleColumns)) {
                $headings[] = $this->availableColumns[$columnKey]['title'] ?? ucfirst(str_replace('_', ' ', $columnKey));
            }
        }

        return $headings;
    }

    protected function getColumnValue($bike, $columnKey)
    {
        switch ($columnKey) {
            case 'bike_code':
                return $bike->bike_code;

            case 'plate':
                return $bike->plate;

            case 'rider_name':
                $rider = DB::table('riders')->where('id', $bike->rider_id)->first();
                return $rider ? $rider->name : '-';

            case 'rider_id':
                $rider = DB::table('riders')->where('id', $bike->rider_id)->first();
                return $rider ? $rider->rider_id : '-';

            case 'emirates':
                return $bike->emirates;

            case 'company':
                $company = DB::table('leasing_companies')->where('id', $bike->company)->first();
                return $company ? $company->name : '-';

            case 'customer_id':
                $customer = DB::table('customers')->where('id', $bike->customer_id)->first();
                return $customer ? $customer->name : '-';

            case 'expiry_date':
                return $bike->expiry_date ? \Carbon\Carbon::parse($bike->expiry_date)->format('d M Y') : '-';

            case 'warehouse':
                return $bike->warehouse ?? '-';

            case 'status':
                return $bike->status == 1 ? 'Active' : 'Inactive';

            case 'created_by':
                $user = DB::table('users')->where('id', $bike->created_by)->first();
                return $user ? $user->name : '-';

            case 'updated_by':
                $user = DB::table('users')->where('id', $bike->updated_by)->first();
                return $user ? $user->name : '-';

            case 'chassis_number':
                return $bike->chassis_number;

            case 'color':
                return $bike->color;

            case 'model':
                return $bike->model;

            case 'model_type':
                return $bike->model_type;

            case 'engine':
                return $bike->engine;

            case 'registration_date':
                return $bike->registration_date ? \Carbon\Carbon::parse($bike->registration_date)->format('d M Y') : '-';

            case 'insurance_expiry':
                return $bike->insurance_expiry ? \Carbon\Carbon::parse($bike->insurance_expiry)->format('d M Y') : '-';

            case 'insurance_co':
                return $bike->insurance_co;

            case 'policy_no':
                return $bike->policy_no;

            case 'contract_number':
                return $bike->contract_number;

            case 'traffic_file_number':
                return $bike->traffic_file_number;

            default:
                return '-';
        }
    }

    /**
     * Get all available columns for UI display
     */
    public static function getAvailableColumns()
    {
        return (new self())->availableColumns;
    }
}
