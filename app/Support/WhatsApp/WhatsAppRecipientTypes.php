<?php

namespace App\Support\WhatsApp;

use App\Models\BikeRentCompany;
use App\Models\Customers;
use App\Models\Employee;
use App\Models\LeasingCompanies;
use App\Models\Recruiters;
use App\Models\Riders;
use App\Models\Settings;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WhatsAppRecipientTypes
{
    public const UNKNOWN = 'unknown';

    /**
     * @return array<string, array{
     *   morph: ?string,
     *   model: ?class-string<Model>,
     *   phone_fields: list<string>,
     *   menu_label_keys: list<string>,
     *   label: string,
     *   scopes?: array<string, mixed>
     * }>
     */
    public static function definitions(): array
    {
        return [
            'riders' => [
                'morph' => 'rider',
                'model' => Riders::class,
                'phone_fields' => ['personal_contact', 'company_contact'],
                'menu_label_keys' => ['riders'],
                'label' => 'Riders',
            ],
            // Do NOT register a short morph alias for User — Spatie permission
            // pivots store App\Models\User; a global 'user' morphMap alias breaks roles/menu.
            'users' => [
                'morph' => User::class,
                'model' => User::class,
                'phone_fields' => ['company_contact', 'phone'],
                'menu_label_keys' => ['users'],
                'label' => 'Users',
            ],
            'employees' => [
                'morph' => 'employee',
                'model' => Employee::class,
                'phone_fields' => ['personal_contact', 'company_contact', 'emergency_contact'],
                'menu_label_keys' => ['employees'],
                'label' => 'Employees',
            ],
            'customers' => [
                'morph' => 'customer',
                'model' => Customers::class,
                'phone_fields' => ['contact_number'],
                'menu_label_keys' => ['customers'],
                'label' => 'Customers',
            ],
            'recruiters' => [
                'morph' => 'recruiter',
                'model' => Recruiters::class,
                'phone_fields' => ['contact_number'],
                'menu_label_keys' => ['recruiters'],
                'label' => 'Recruiters',
            ],
            'bike_rent_customers' => [
                'morph' => 'bike_rent_customer',
                'model' => BikeRentCompany::class,
                'phone_fields' => ['company_contact'],
                'menu_label_keys' => ['bike_rent_customers', 'bike_on_rent'],
                'label' => 'Bike rent customers',
                'scopes' => ['customer_type' => 'bike_rental'],
            ],
            'leasing_companies' => [
                'morph' => 'leasing_company',
                'model' => LeasingCompanies::class,
                'phone_fields' => ['contact_number'],
                'menu_label_keys' => ['leasing_companies'],
                'label' => 'Leasing companies',
            ],
            'garage_customers' => [
                'morph' => 'garage_customer',
                'model' => BikeRentCompany::class,
                'phone_fields' => ['company_contact'],
                'menu_label_keys' => ['garage_customers'],
                'label' => 'Garage customers',
                'scopes' => ['customer_type' => 'garage'],
            ],
            'suppliers' => [
                'morph' => 'supplier',
                'model' => Supplier::class,
                'phone_fields' => ['phone'],
                'menu_label_keys' => ['supplier', 'suppliers'],
                'label' => 'Suppliers',
            ],
            self::UNKNOWN => [
                'morph' => null,
                'model' => null,
                'phone_fields' => [],
                'menu_label_keys' => [],
                'label' => 'Unknown',
            ],
        ];
    }

    /**
     * @return list<string>
     */
    public static function filterKeys(): array
    {
        return array_keys(self::definitions());
    }

    public static function labelFor(string $key): string
    {
        $def = self::definitions()[$key] ?? null;
        if (! $def) {
            return $key;
        }

        foreach ($def['menu_label_keys'] as $menuKey) {
            $label = Settings::getMenuLabel($menuKey);
            if (is_string($label) && $label !== '' && $label !== $menuKey) {
                return $label;
            }
            if (is_string($label) && $label !== '') {
                return $label;
            }
        }

        return $def['label'];
    }

    public static function morphAliasForKey(string $key): ?string
    {
        return self::definitions()[$key]['morph'] ?? null;
    }

    public static function keyForMorph(?string $morph): ?string
    {
        if ($morph === null || $morph === '') {
            return self::UNKNOWN;
        }

        foreach (self::definitions() as $key => $def) {
            $alias = $def['morph'] ?? null;
            if ($alias === $morph) {
                return $key;
            }
            // Legacy short alias if any rows were stored as "user".
            if ($key === 'users' && in_array($morph, ['user', User::class], true)) {
                return $key;
            }
        }

        return null;
    }

    /**
     * @return array<string, class-string<Model>>
     */
    public static function morphMap(): array
    {
        $map = [];
        foreach (self::definitions() as $def) {
            $morph = $def['morph'] ?? null;
            $model = $def['model'] ?? null;
            // Skip FQCN morph keys and User (must stay unaliased for Spatie).
            if ($morph && $model && ! str_contains($morph, '\\') && $model !== User::class) {
                $map[$morph] = $model;
            }
        }

        return $map;
    }

    /**
     * @return Builder|null
     */
    public static function baseQuery(string $key): ?Builder
    {
        $def = self::definitions()[$key] ?? null;
        if (! $def || ! $def['model']) {
            return null;
        }

        /** @var class-string<Model> $model */
        $model = $def['model'];
        $query = $model::query();

        if (in_array(SoftDeletes::class, class_uses_recursive($model), true)) {
            $query->withTrashed();
        }

        foreach ($def['scopes'] ?? [] as $column => $value) {
            $query->where($column, $value);
        }

        return $query;
    }

    public static function displayName(Model $model): string
    {
        foreach (['name', 'company_name', 'email', 'username'] as $attr) {
            $value = trim((string) ($model->{$attr} ?? ''));
            if ($value !== '') {
                return $value;
            }
        }

        return class_basename($model).' #'.$model->getKey();
    }

    /**
     * Public URL for a known participant photo, or null when unavailable.
     */
    public static function photoUrl(?Model $model): ?string
    {
        if (! $model) {
            return null;
        }

        $imageName = trim((string) ($model->image_name ?? ''));
        if ($imageName === '' || in_array(strtolower($imageName), ['default.png', 'default.jpg'], true)) {
            return null;
        }

        if ($model instanceof Riders) {
            return storage_url('profile/'.$imageName) ?: null;
        }

        if ($model instanceof User) {
            return storage_url('uploads/'.$imageName) ?: null;
        }

        // Other recipient types may store a generic image_name on the public disk.
        if (isset($model->image_name)) {
            return storage_url('profile/'.$imageName)
                ?: (storage_url('uploads/'.$imageName) ?: null);
        }

        return null;
    }

    public static function isTrashed(Model $model): bool
    {
        return method_exists($model, 'trashed') && $model->trashed();
    }
}
