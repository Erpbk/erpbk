<?php

namespace App\Exports;

use App\Helpers\General;
use App\Models\Riders;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CustomizableRiderExport implements FromCollection, WithHeadings, WithMapping
{
    protected $visibleColumns;
    protected $columnOrder;
    protected $filters;

    // Define all available columns with their keys, titles, and data mappings
    protected $availableColumns = [
        'rider_id' => [
            'title' => 'Rider ID',
            'data' => 'rider_id'
        ],
        'courier_id' => [
            'title' => 'Courier ID',
            'data' => 'courier_id'
        ],
        'name' => [
            'title' => 'Name',
            'data' => 'name'
        ],
        'company_contact' => [
            'title' => 'Contact',
            'data' => 'company_contact'
        ],
        'fleet_supervisor' => [
            'title' => 'Fleet Supv',
            'data' => 'fleet_supervisor'
        ],
        'emirate_hub' => [
            'title' => 'Hub',
            'data' => 'emirate_hub'
        ],
        'customer_id' => [
            'title' => 'Customer',
            'data' => 'customer_id'
        ],
        'designation' => [
            'title' => 'Desig',
            'data' => 'designation'
        ],
        'bike' => [
            'title' => 'Bike',
            'data' => 'bike'
        ],
        'status' => [
            'title' => 'Status',
            'data' => 'status'
        ],
        'shift' => [
            'title' => 'Shift',
            'data' => 'shift'
        ],
        'attendance' => [
            'title' => 'ATTN',
            'data' => 'attendance'
        ],
        'orders_sum' => [
            'title' => 'Orders',
            'data' => 'orders_sum'
        ],
        'days' => [
            'title' => 'Days',
            'data' => 'days'
        ],
        'balance' => [
            'title' => 'Balance',
            'data' => 'balance'
        ],
        // Additional fields from the original export
        'ethnicity' => [
            'title' => 'Ethnicity',
            'data' => 'ethnicity'
        ],
        'salary_model' => [
            'title' => 'Salary Model',
            'data' => 'salary_model'
        ],
        'visa_occupation' => [
            'title' => 'Occupation on Visa',
            'data' => 'visa_occupation'
        ],
        'personal_contact' => [
            'title' => 'Personal Contact',
            'data' => 'personal_contact'
        ],
        'doj' => [
            'title' => 'Joining Date',
            'data' => 'doj'
        ],
        'dob' => [
            'title' => 'DOB',
            'data' => 'dob'
        ],
        'emirate_id' => [
            'title' => 'EID',
            'data' => 'emirate_id'
        ],
        'emirate_exp' => [
            'title' => 'EID Expiry',
            'data' => 'emirate_exp'
        ],
        'nationality' => [
            'title' => 'Nationality',
            'data' => 'nationality'
        ],
        'passport' => [
            'title' => 'Passport No.',
            'data' => 'passport'
        ],
        'passport_handover' => [
            'title' => 'Passport Handover Status',
            'data' => 'passport_handover'
        ],
        'cdm_deposit_id' => [
            'title' => 'CDM ID',
            'data' => 'cdm_deposit_id'
        ],
        'personal_email' => [
            'title' => 'Email',
            'data' => 'personal_email'
        ],
        'wps' => [
            'title' => 'WPS/NON WPS',
            'data' => 'wps'
        ],
        'c3_card' => [
            'title' => 'C3 Card',
            'data' => 'c3_card'
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
        $query = Riders::with(['bikes', 'country']);

        // Apply any filters that might be passed
        if (!empty($this->filters)) {
            foreach ($this->filters as $key => $value) {
                if (!empty($value)) {
                    switch ($key) {
                        case 'rider_id':
                            $query->where('rider_id', $value);
                            break;
                        case 'name':
                            $query->where('name', 'like', "%{$value}%");
                            break;
                        case 'fleet_supervisor':
                            $query->where('fleet_supervisor', $value);
                            break;
                        case 'status':
                            $query->where('status', $value);
                            break;
                        case 'emirate_hub':
                            $query->where('emirate_hub', $value);
                            break;
                            // Add more filter cases as needed
                    }
                }
            }
        }

        return $query->get();
    }

    public function map($rider): array
    {
        $data = [];

        // Map each column according to the specified order
        foreach ($this->columnOrder as $columnKey) {
            // Only include visible columns
            if (in_array($columnKey, $this->visibleColumns)) {
                $data[] = $this->getColumnValue($rider, $columnKey);
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

    protected function getColumnValue($rider, $columnKey)
    {
        switch ($columnKey) {
            case 'rider_id':
                return $rider->rider_id;

            case 'courier_id':
                return $rider->courier_id;

            case 'name':
                return $rider->name ?? '';

            case 'company_contact':
                return $rider->company_contact;

            case 'fleet_supervisor':
                return $rider->fleet_supervisor;

            case 'emirate_hub':
                return $rider->emirate_hub;

            case 'customer_id':
                return DB::table('customers')->where('id', $rider->customer_id)->value('name') ?? '-';

            case 'designation':
                return $rider->designation;

            case 'bike':
                $bike = DB::table('bikes')->where('rider_id', $rider->id)->first();
                return $bike ? $bike->plate : '-';

            case 'status':
                $hasActiveBike = DB::table('bikes')
                    ->where('rider_id', $rider->id)
                    ->where('warehouse', 'Active')
                    ->exists();
                return $hasActiveBike ? 'Active' : 'Inactive';

            case 'shift':
                return $rider->shift;

            case 'attendance':
                return $rider->attendance ?? '-';

            case 'orders_sum':
                return DB::table('rider_activities')
                    ->where('d_rider_id', $rider->rider_id)
                    ->whereMonth('date', now()->month)
                    ->whereYear('date', now()->year)
                    ->sum('delivered_orders') ?: '-';

            case 'days':
                return DB::table('rider_activities')
                    ->where('d_rider_id', $rider->rider_id)
                    ->whereMonth('date', now()->month)
                    ->whereYear('date', now()->year)
                    ->count('date') ?: '-';

            case 'balance':
                return \App\Helpers\Accounts::getBalance($rider->account_id) ?: '-';

            case 'ethnicity':
                return $rider->ethnicity;

            case 'salary_model':
                return $rider->salary_model;

            case 'visa_occupation':
                return $rider->visa_occupation;

            case 'personal_contact':
                return $rider->personal_contact;

            case 'doj':
                return $rider->doj;

            case 'dob':
                return $rider->dob;

            case 'emirate_id':
                return $rider->emirate_id;

            case 'emirate_exp':
                return $rider->emirate_exp;

            case 'nationality':
                return $rider->country?->name;

            case 'passport':
                return $rider->passport;

            case 'passport_handover':
                return $rider->passport_handover;

            case 'cdm_deposit_id':
                return $rider->cdm_deposit_id;

            case 'personal_email':
                return $rider->personal_email;

            case 'wps':
                return $rider->wps;

            case 'c3_card':
                return $rider->c3_card;

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
