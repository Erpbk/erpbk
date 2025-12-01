<?php

namespace App\Imports;

use App\Models\Bikes;
use App\Models\Riders;
use App\Models\LeasingCompanies;
use App\Models\Customers;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use Carbon\Carbon;

class ImportBikes implements ToCollection, WithHeadingRow
{
    protected $errors = [];
    protected $successCount = 0;
    protected $errorCount = 0;

    public function collection(Collection $rows)
    {
        $rowNumber = 2; // Start from row 2 since row 1 is header

        foreach ($rows as $row) {
            try {
                // Validate the row data
                $validator = $this->validateRow($row, $rowNumber);

                if ($validator->fails()) {
                    $this->errors[] = "Row {$rowNumber}: " . implode(', ', $validator->errors()->all());
                    $this->errorCount++;
                    $rowNumber++;
                    continue;
                }

                // Prepare data for bike creation
                $bikeData = $this->prepareBikeData($row);

                // Create or update bike
                $this->createOrUpdateBike($bikeData);

                $this->successCount++;
            } catch (\Exception $e) {
                $this->errors[] = "Row {$rowNumber}: " . $e->getMessage();
                $this->errorCount++;
            }

            $rowNumber++;
        }
    }

    protected function validateRow($row, $rowNumber)
    {
        $rules = [
            'plate' => 'required|string|max:100',
            'vehicle_type' => 'nullable|string|max:100',
            'chassis_number' => 'nullable|string|max:100',
            'color' => 'nullable|string|max:100',
            'model' => 'nullable|string|max:100',
            'model_type' => 'nullable|string|max:100',
            'engine' => 'nullable|string|max:100',
            'bike_code' => 'nullable|string|max:100',
            'emirates' => 'nullable|string|max:100',
            'warehouse' => 'nullable|string|max:50',
            'status' => 'nullable|in:1,0',
            'registration_date' => 'nullable|date',
            'expiry_date' => 'nullable|date',
            'insurance_expiry' => 'nullable|date',
            'insurance_co' => 'nullable|string|max:255',
            'policy_no' => 'nullable|string|max:100',
            'contract_number' => 'nullable|string|max:50',
            'traffic_file_number' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
        ];

        $messages = [
            'plate.required' => 'Plate number is required',
            'status.in' => 'Status must be 1 (Active) or 0 (Inactive)',
            'registration_date.date' => 'Registration date must be a valid date',
            'expiry_date.date' => 'Expiry date must be a valid date',
            'insurance_expiry.date' => 'Insurance expiry date must be a valid date',
        ];

        $validator = Validator::make($row->toArray(), $rules, $messages);

        // Add custom validation for duplicate checking
        $validator->after(function ($validator) use ($row) {
            // Check for duplicate plate number
            if (!empty($row['plate'])) {
                $existingPlate = Bikes::where('plate', $row['plate'])->first();
                if ($existingPlate) {
                    $validator->errors()->add('plate', 'Plate number "' . $row['plate'] . '" already exists in the database');
                }
            }

            // Check for duplicate chassis number
            if (!empty($row['chassis_number'])) {
                $existingChassis = Bikes::where('chassis_number', $row['chassis_number'])->first();
                if ($existingChassis) {
                    $validator->errors()->add('chassis_number', 'Chassis number "' . $row['chassis_number'] . '" already exists in the database');
                }
            }

            // Check for duplicate engine number
            if (!empty($row['engine'])) {
                $existingEngine = Bikes::where('engine', $row['engine'])->first();
                if ($existingEngine) {
                    $validator->errors()->add('engine', 'Engine number "' . $row['engine'] . '" already exists in the database');
                }
            }
        });

        return $validator;
    }

    protected function prepareBikeData($row)
    {
        $data = [
            'plate' => $row['plate'] ?? '',
            'vehicle_type' => $row['vehicle_type'] ?? null,
            'chassis_number' => $row['chassis_number'] ?? null,
            'color' => $row['color'] ?? null,
            'model' => $row['model'] ?? null,
            'model_type' => $row['model_type'] ?? null,
            'engine' => $row['engine'] ?? null,
            'bike_code' => $row['bike_code'] ?? null,
            'emirates' => $row['emirates'] ?? null,
            'warehouse' => $row['warehouse'] ?? null,
            'status' => isset($row['status']) ? (int)$row['status'] : 1,
            'insurance_co' => $row['insurance_co'] ?? null,
            'policy_no' => $row['policy_no'] ?? null,
            'contract_number' => $row['contract_number'] ?? null,
            'traffic_file_number' => $row['traffic_file_number'] ?? null,
            'notes' => $row['notes'] ?? null,
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ];

        // Handle date fields
        if (!empty($row['registration_date'])) {
            $data['registration_date'] = $this->parseDate($row['registration_date']);
        }

        if (!empty($row['expiry_date'])) {
            $data['expiry_date'] = $this->parseDate($row['expiry_date']);
        }

        if (!empty($row['insurance_expiry'])) {
            $data['insurance_expiry'] = $this->parseDate($row['insurance_expiry']);
        }

        // Handle foreign key relationships
        if (!empty($row['rider_name'])) {
            $rider = Riders::where('name', 'like', '%' . $row['rider_name'] . '%')->first();
            $data['rider_id'] = $rider ? $rider->id : null;
        }

        if (!empty($row['company_name'])) {
            $company = LeasingCompanies::where('name', 'like', '%' . $row['company_name'] . '%')->first();
            $data['company'] = $company ? $company->id : null;
        }

        if (!empty($row['customer_name'])) {
            $customer = Customers::where('name', 'like', '%' . $row['customer_name'] . '%')->first();
            $data['customer_id'] = $customer ? $customer->id : null;
        }

        return $data;
    }

    protected function createOrUpdateBike($data)
    {
        // Create new bike (duplicates are already prevented by validation)
        Bikes::create($data);
    }

    protected function parseDate($value)
    {
        if (empty($value)) {
            return null;
        }

        // Handle Excel date format
        if (is_numeric($value)) {
            try {
                return Carbon::instance(Date::excelToDateTimeObject($value))->format('Y-m-d');
            } catch (\Exception $e) {
                // If Excel date parsing fails, try regular date parsing
                return Carbon::parse($value)->format('Y-m-d');
            }
        }

        // Try parsing as regular date string
        try {
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get import results
     */
    public function getResults()
    {
        return [
            'success_count' => $this->successCount,
            'error_count' => $this->errorCount,
            'errors' => $this->errors,
        ];
    }

    /**
     * Check if there were any errors during import
     */
    public function hasErrors()
    {
        return !empty($this->errors);
    }

    /**
     * Get all errors
     */
    public function getErrors()
    {
        return $this->errors;
    }
}
