<?php

namespace App\Services\Agreements;

use App\Models\Company;
use App\Models\Employee;
use App\Models\Riders;
use App\Support\CompanyContext;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class AgreementPlaceholderResolver
{
    public function resolveForModule(string $module, Model $record, ?string $agreementDate = null): array
    {
        return match ($module) {
            'riders' => $record instanceof Riders
                ? $this->resolveForRider($record, $agreementDate)
                : $this->resolveForRider(Riders::query()->findOrFail($record->getKey()), $agreementDate),
            'employees' => $record instanceof Employee
                ? $this->resolveForEmployee($record, $agreementDate)
                : $this->resolveForEmployee(Employee::query()->findOrFail($record->getKey()), $agreementDate),
            default => [],
        };
    }

    public function resolveForEmployee(Employee $employee, ?string $agreementDate = null): array
    {
        $employee->loadMissing(['branch', 'department']);

        $company = request()?->attributes->get('company') ?? Company::find(CompanyContext::id());
        $custom = is_array($employee->custom_field_values) ? $employee->custom_field_values : [];

        $agreementDateFormatted = $agreementDate
            ? Carbon::parse($agreementDate)->format('d-M-Y')
            : now()->format('d-M-Y');

        $email = (string) ($employee->company_email ?: $employee->personal_email ?: '');

        return [
            '{rider_name}' => (string) ($employee->name ?? ''),
            '{rider_code}' => (string) ($employee->employee_id ?? ''),
            '{rider_email}' => $email,
            '{rider_phone}' => (string) ($employee->company_contact ?: $employee->personal_contact ?: ''),
            '{rider_cnic}' => (string) ($employee->emirate_id ?? ''),
            '{rider_passport_number}' => (string) ($employee->passport ?? ''),
            '{rider_nationality}' => '',
            '{rider_date_of_birth}' => $this->formatDate($employee->dob),
            '{rider_gender}' => '',
            '{rider_address}' => (string) ($employee->address ?? ''),
            '{rider_city}' => $company->city ?? '',
            '{rider_country}' => $company->country ?? '',
            '{joining_date}' => $this->formatDate($employee->doj),
            '{designation}' => (string) ($employee->designation ?? ''),
            '{salary}' => (string) ($employee->salary ?? ''),
            '{branch_name}' => (string) ($employee->branch->name ?? ''),
            '{company_name}' => (string) ($company->name ?? config('app.name')),
            '{bike_number}' => '',
            '{bike_model}' => '',
            '{current_date}' => now()->format('d-M-Y'),
            '{agreement_date}' => $agreementDateFormatted,
        ];
    }

    public function resolveForRider(Riders $rider, ?string $agreementDate = null): array
    {
        $rider->loadMissing(['branch', 'bikes', 'country']);

        $company = request()?->attributes->get('company') ?? Company::find(CompanyContext::id());
        $custom = is_array($rider->custom_field_values) ? $rider->custom_field_values : [];

        $agreementDateFormatted = $agreementDate
            ? Carbon::parse($agreementDate)->format('d-M-Y')
            : now()->format('d-M-Y');

        $nationality = '';
        if ($rider->country) {
            $nationality = $rider->country->name ?? '';
        } elseif (!empty($rider->nationality) && !is_numeric($rider->nationality)) {
            $nationality = (string) $rider->nationality;
        }

        $bike = $rider->bikes;

        return [
            '{rider_name}' => (string) ($rider->name ?? ''),
            '{rider_code}' => (string) ($rider->rider_id ?? ''),
            '{rider_email}' => (string) ($rider->email ?? ''),
            '{rider_phone}' => $this->customValue($rider, $custom, 'personal_contact'),
            '{rider_cnic}' => $this->customValue($rider, $custom, 'nic') ?: (string) ($rider->emirate_id ?? ''),
            '{rider_passport_number}' => (string) ($rider->passport ?? ''),
            '{rider_nationality}' => $nationality,
            '{rider_date_of_birth}' => $this->formatDate($rider->dob),
            '{rider_gender}' => $this->customValue($rider, $custom, 'gender') ?: (string) ($rider->ethnicity ?? ''),
            '{rider_address}' => $this->customValue($rider, $custom, 'address'),
            '{rider_city}' => $this->customValue($rider, $custom, 'city') ?: ($company->city ?? ''),
            '{rider_country}' => $this->customValue($rider, $custom, 'country') ?: ($company->country ?? ''),
            '{joining_date}' => $this->formatDate($rider->doj),
            '{designation}' => (string) ($rider->designation ?? ''),
            '{salary}' => $this->customValue($rider, $custom, 'salary') ?: $this->customValue($rider, $custom, 'salary_model'),
            '{branch_name}' => (string) ($rider->branch->name ?? ''),
            '{company_name}' => (string) ($company->name ?? config('app.name')),
            '{bike_number}' => (string) ($bike->plate ?? $bike->bike_number ?? ''),
            '{bike_model}' => (string) ($bike->model ?? $bike->vehicle_type ?? ''),
            '{current_date}' => now()->format('d-M-Y'),
            '{agreement_date}' => $agreementDateFormatted,
        ];
    }

    public function replace(string $html, array $map): string
    {
        return str_replace(array_keys($map), array_values($map), $html);
    }

    private function customValue(Riders $rider, array $custom, string $key): string
    {
        if (array_key_exists($key, $custom) && $custom[$key] !== null && $custom[$key] !== '') {
            return (string) $custom[$key];
        }

        if ($rider->getAttribute($key) !== null && $rider->getAttribute($key) !== '') {
            return (string) $rider->getAttribute($key);
        }

        return '';
    }

    private function formatDate(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }
        try {
            return Carbon::parse($value)->format('d-M-Y');
        } catch (\Throwable) {
            return (string) $value;
        }
    }
}
