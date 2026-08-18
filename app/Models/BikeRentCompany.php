<?php

namespace App\Models;

use App\Helpers\IConstants;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\LogsActivity;
use App\Traits\HasActiveStatus;
use App\Traits\BranchScope;

class BikeRentCompany extends BaseModel
{
    use LogsActivity, HasActiveStatus, SoftDeletes, BranchScope;

    public const PARTY_COMPANY = 'company';
    public const PARTY_INDIVIDUAL = 'individual';

    public $table = 'bike_rent_companies';

    public $fillable = [
        'branch_id',
        'name',
        'company_contact',
        'customer_type',
        'party_type',
        'email',
        'address',
        'emirates_id',
        'emirates_expiry',
        'passport_no',
        'passport_expiry',
        'dob',
        'nationality',
        'license_no',
        'license_expiry',
        'status',
        'account_id',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'name' => 'string',
        'company_contact' => 'string',
        'email' => 'string',
        'address' => 'string',
        'status' => 'integer',
        'emirates_expiry' => 'date',
        'passport_expiry' => 'date',
        'dob' => 'date',
        'license_expiry' => 'date',
    ];

    protected $dates = ['deleted_at'];

    public static array $rules = [
        'name' => 'required|string|max:255',
        'company_contact' => 'nullable|string|max:255',
        'email' => 'nullable|email|max:255',
        'address' => 'nullable|string|max:500',
        'branch_id' => 'nullable',
        'status' => 'nullable',
        'party_type' => 'nullable|in:company,individual',
        'emirates_id' => 'nullable|string|max:255',
        'emirates_expiry' => 'nullable|date',
        'passport_no' => 'nullable|string|max:255',
        'passport_expiry' => 'nullable|date',
        'dob' => 'nullable|date',
        'nationality' => 'nullable|string|max:255',
        'license_no' => 'nullable|string|max:255',
        'license_expiry' => 'nullable|date',
    ];

    public static function individualFieldKeys(): array
    {
        return [
            'emirates_id',
            'emirates_expiry',
            'passport_no',
            'passport_expiry',
            'dob',
            'nationality',
            'license_no',
            'license_expiry',
        ];
    }

    public function isIndividual(): bool
    {
        return ($this->party_type ?? self::PARTY_COMPANY) === self::PARTY_INDIVIDUAL;
    }

    public function assignmentHistoryLabel(): string
    {
        if (($this->customer_type ?? '') === 'garage') {
            return 'Garage customer';
        }

        return $this->isIndividual() ? 'Individual' : 'Rental customer';
    }

    public function account()
    {
        return $this->hasOne(Accounts::class, 'id', 'account_id');
    }

    public function transactions()
    {
        return $this->hasMany(Transactions::class, 'account_id', 'account_id');
    }

    public static function dropdown()
    {
        return self::select('id', 'name')->where('status', IConstants::ACTIVE)->orderBy('name')->pluck('name', 'id')->prepend('Select', '');
    }

    /**
     * Bike-on-rent customers grouped as companies vs individuals.
     *
     * @return array<string, mixed>
     */
    public static function rentalAssignDropdown(): array
    {
        $rows = self::query()
            ->where('status', IConstants::ACTIVE)
            ->where('customer_type', 'bike_rental')
            ->orderBy('name')
            ->get(['id', 'name', 'party_type']);

        $companies = [];
        $individuals = [];
        foreach ($rows as $row) {
            if ($row->party_type === self::PARTY_INDIVIDUAL) {
                $individuals[$row->id] = $row->name;
            } else {
                $companies[$row->id] = $row->name;
            }
        }

        $grouped = [];
        if ($companies !== []) {
            $grouped['Rental companies'] = $companies;
        }
        if ($individuals !== []) {
            $grouped['Individuals'] = $individuals;
        }

        return ['' => 'Select'] + $grouped;
    }

    /**
     * @return array<int|string, string>
     */
    public static function garageAssignDropdown(): array
    {
        return self::query()
            ->where('status', IConstants::ACTIVE)
            ->where('customer_type', 'garage')
            ->orderBy('name')
            ->pluck('name', 'id')
            ->prepend('Select', '')
            ->all();
    }
}
