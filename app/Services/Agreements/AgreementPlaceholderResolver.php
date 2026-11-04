<?php

namespace App\Services\Agreements;

use App\Models\BikeRentCompany;
use App\Models\Bikes;
use App\Models\Company;
use App\Models\Employee;
use App\Models\FuelCards;
use App\Models\Riders;
use App\Models\Sims;
use App\Support\CompanyContext;
use App\Support\ModuleFieldSource;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;

class AgreementPlaceholderResolver
{
    /**
     * Build replacement map from the admin catalog (system + module DB fields).
     *
     * @return array<string, string>
     */
    public function resolveForModule(string $module, Model $record, ?string $agreementDate = null): array
    {
        $map = $this->systemMap();

        foreach (app(AgreementPlaceholderCatalog::class)->placeholdersForModule($module) as $row) {
            $token = (string) ($row->placeholder ?? '');
            if ($token === '') {
                continue;
            }

            $sourceKey = trim((string) ($row->source_key ?: trim($token, '{}')));
            $map[$token] = $this->resolveSourceKey($module, $record, $sourceKey);
        }

        return $map;
    }

    /**
     * @return array<string, string>
     */
    private function systemMap(): array
    {
        return [
            '{company_name}' => $this->companyName(),
            '{company_contact}' => $this->companyContact(),
            '{company_address}' => $this->companyAddress(),
            '{current_date}' => now()->format('d-M-Y'),
        ];
    }

    private function resolveSourceKey(string $module, Model $record, string $sourceKey): string
    {
        if ($sourceKey === 'current_date') {
            return now()->format('d-M-Y');
        }

        if ($sourceKey === 'company_name') {
            return $this->companyName();
        }

        if ($sourceKey === 'company_contact') {
            return $this->companyContact();
        }

        if ($sourceKey === 'company_address') {
            return $this->companyAddress();
        }

        if (str_starts_with($sourceKey, 'other.')) {
            return $this->resolveOtherSourceKey($module, $record, substr($sourceKey, strlen('other.')));
        }

        if (str_starts_with($sourceKey, 'assignedTo.')) {
            return $this->resolveAssignedToSourceKey($module, $record, substr($sourceKey, strlen('assignedTo.')));
        }

        if (str_starts_with($sourceKey, 'assignee.')) {
            return $this->resolveAssigneeSourceKey($module, $record, substr($sourceKey, strlen('assignee.')));
        }

        if (str_starts_with($sourceKey, 'lostBy.')) {
            return $this->resolveLostBySourceKey($module, $record, substr($sourceKey, strlen('lostBy.')));
        }

        if (str_contains($sourceKey, '.')) {
            [$relation, $field] = explode('.', $sourceKey, 2);
            $related = $this->resolveRelatedRecord($record, trim($relation));
            if (! $related) {
                return '';
            }

            return $this->formatAttribute($related->getAttribute(trim($field)));
        }

        try {
            if (ModuleFieldSource::isSchemaFieldKey($module, $sourceKey)) {
                return $this->formatAttribute($record->getAttribute($sourceKey));
            }
        } catch (\Throwable) {
            // Fall through to attribute read.
        }

        return $this->formatAttribute($record->getAttribute($sourceKey));
    }

    private function resolveRelatedRecord(Model $record, string $relation): ?Model
    {
        if ($relation === '') {
            return null;
        }

        try {
            if ($record->relationLoaded($relation)) {
                $loaded = $record->getRelation($relation);
                if ($loaded instanceof Model) {
                    return $loaded;
                }
                if ($loaded === null) {
                    return null;
                }
            }
        } catch (\Throwable) {
            // Continue.
        }

        try {
            if (method_exists($record, $relation)) {
                $value = $record->{$relation}();
                if ($value instanceof Relation) {
                    $related = $value->getResults();

                    return $related instanceof Model ? $related : null;
                }
            }
        } catch (\Throwable) {
            // Fall through to FK lookup.
        }

        $meta = app(AgreementPlaceholderCatalog::class)->foreignKeyMetaForRelation($relation);
        $modelClass = $meta['model'] ?? null;
        if (! is_string($modelClass) || ! is_subclass_of($modelClass, Model::class)) {
            return null;
        }

        $fkColumn = isset($meta['fk_column']) ? (string) $meta['fk_column'] : null;
        if ($fkColumn === null || $fkColumn === '') {
            $fkColumn = $relation.'_id';
        }

        $fk = $record->getAttribute($fkColumn);
        if ($fk === null || $fk === '') {
            return null;
        }

        try {
            return $modelClass::query()->find($fk);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Resolve custom "Related: Other" sources (module-specific computed values).
     */
    private function resolveOtherSourceKey(string $module, Model $record, string $leaf): string
    {
        $leaf = trim($leaf);
        if ($leaf === '') {
            return '';
        }

        $allowed = app(AgreementPlaceholderCatalog::class)->relatedOtherSourceOptions($module);
        if (! array_key_exists('other.'.$leaf, $allowed)) {
            return '';
        }

        return match ($module) {
            'riders' => $this->resolveRiderOtherSource($record, $leaf),
            'bike_on_rent', 'garages_customers' => $this->resolveAssignedVehicleListOtherSource($record, $leaf, 'rental_company_id'),
            'leasing_companies' => $this->resolveAssignedVehicleListOtherSource($record, $leaf, 'company'),
            default => '',
        };
    }

    private function resolveAssignedVehicleListOtherSource(Model $record, string $leaf, string $bikeFkColumn): string
    {
        return match ($leaf) {
            'assigned_vehicle_list' => $this->formatAssignedVehicleList($record, $bikeFkColumn),
            default => '',
        };
    }

    /**
     * Displayable list of bikes linked to a company/customer record.
     * Each item is bike_code + emirates + plate (e.g. 2DXB21652), space-separated.
     */
    private function formatAssignedVehicleList(Model $record, string $bikeFkColumn): string
    {
        $labels = [];
        foreach ($this->bikesAssignedWhere($record, $bikeFkColumn) as $bike) {
            if (! $bike instanceof Bikes) {
                continue;
            }
            $label = $this->formatBikeCodeEmiratesPlateConcat($bike);
            if ($label !== '') {
                $labels[] = $label;
            }
        }

        if ($labels === []) {
            return '';
        }

        return implode(' ', array_map(
            static fn (string $label): string => htmlspecialchars($label, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            $labels
        ));
    }

    /**
     * @return \Illuminate\Support\Collection<int, Bikes>
     */
    protected function bikesAssignedWhere(Model $record, string $bikeFkColumn): \Illuminate\Support\Collection
    {
        $ownerId = $record->getKey();
        if ($ownerId === null || $ownerId === '' || $bikeFkColumn === '') {
            return collect();
        }

        try {
            return Bikes::query()
                ->where($bikeFkColumn, $ownerId)
                ->orderBy('bike_code')
                ->orderBy('emirates')
                ->orderBy('plate')
                ->get(['bike_code', 'emirates', 'plate']);
        } catch (\Throwable) {
            return collect();
        }
    }

    /**
     * Format: bike_codeemiratesplate (e.g. 2DXB21652).
     */
    private function formatBikeCodeEmiratesPlateConcat(Bikes $bike): string
    {
        return trim((string) ($bike->bike_code ?? ''))
            .trim((string) ($bike->emirates ?? ''))
            .trim((string) ($bike->plate ?? ''));
    }

    /**
     * Resolve unified "Assigned To:" sources (bike → rider or rental/garage customer).
     */
    private function resolveAssignedToSourceKey(string $module, Model $record, string $leaf): string
    {
        $leaf = trim($leaf);
        if ($leaf === '') {
            return '';
        }

        $allowed = app(AgreementPlaceholderCatalog::class)->assignedToSourceOptions($module);
        if (! array_key_exists('assignedTo.'.$leaf, $allowed)) {
            return '';
        }

        return match ($module) {
            'bikes' => $this->resolveBikeAssignedToSource($record, $leaf),
            'fuel_cards' => $this->resolveFuelCardAssignedToSource($record, $leaf),
            default => '',
        };
    }

    private function resolveFuelCardAssignedToSource(Model $record, string $leaf): string
    {
        $rider = $this->relatedModel($record, 'rider');
        if (! $rider instanceof Riders) {
            return '';
        }

        return $this->resolvePersonAgreementFields($rider, $leaf);
    }

    /**
     * Resolve fixed "Related: Assignee" sources (SIM → rider or employee).
     */
    private function resolveAssigneeSourceKey(string $module, Model $record, string $leaf): string
    {
        $leaf = trim($leaf);
        if ($leaf === '') {
            return '';
        }

        $allowed = app(AgreementPlaceholderCatalog::class)->assigneeSourceOptions($module);
        if (! array_key_exists('assignee.'.$leaf, $allowed)) {
            return '';
        }

        return match ($module) {
            'sims' => $this->resolveSimAssigneeSource($record, $leaf),
            default => '',
        };
    }

    private function resolveSimAssigneeSource(Model $record, string $leaf): string
    {
        $person = $this->simAssignedPerson($record);
        if (! $person) {
            return '';
        }

        return $this->resolvePersonAgreementFields($person, $leaf);
    }

    /**
     * Resolve fixed "Related: Lost By" sources (fuel card / SIM → person charged for loss).
     */
    private function resolveLostBySourceKey(string $module, Model $record, string $leaf): string
    {
        $leaf = trim($leaf);
        if ($leaf === '') {
            return '';
        }

        $allowed = app(AgreementPlaceholderCatalog::class)->lostBySourceOptions($module);
        if (! array_key_exists('lostBy.'.$leaf, $allowed)) {
            return '';
        }

        return match ($module) {
            'fuel_cards', 'sims' => $this->resolveLostByPersonSource($record, $leaf),
            default => '',
        };
    }

    private function resolveLostByPersonSource(Model $record, string $leaf): string
    {
        $person = $this->lostByPerson($record);
        if (! $person) {
            return '';
        }

        return $this->resolvePersonAgreementFields($person, $leaf);
    }

    /**
     * Person charged for a lost SIM/fuel card (employee preferred on SIMs, else rider).
     */
    protected function lostByPerson(Model $record): ?Model
    {
        $employee = $this->relatedModel($record, 'lostEmployee');
        if ($employee instanceof Employee) {
            return $employee;
        }

        $rider = $this->relatedModel($record, 'lostRider');

        return $rider instanceof Riders ? $rider : null;
    }

    /**
     * Shared rider/employee field mapping for assignee / lost-by placeholders.
     */
    private function resolvePersonAgreementFields(Model $person, string $leaf): string
    {
        if ($person instanceof Riders) {
            return match ($leaf) {
                'id' => $this->formatAttribute($person->getAttribute('rider_id') ?: $person->getKey()),
                'name' => $this->formatAttribute($person->getAttribute('name')),
                'emirates_id' => $this->formatAttribute($person->getAttribute('emirate_id')),
                'contact_no' => $this->formatAttribute(
                    $person->getAttribute('personal_contact')
                        ?: $person->getAttribute('company_contact')
                ),
                'address' => $this->formatAttribute($person->getAttribute('address')),
                'designation' => $this->formatAttribute($person->getAttribute('designation')),
                'joining_date' => $this->formatDate($person->getAttribute('doj')),
                default => '',
            };
        }

        if ($person instanceof Employee) {
            return match ($leaf) {
                'id' => $this->formatAttribute($person->getAttribute('employee_id') ?: $person->getKey()),
                'name' => $this->formatAttribute($person->getAttribute('name')),
                'emirates_id' => $this->formatAttribute($person->getAttribute('emirate_id')),
                'contact_no' => $this->formatAttribute(
                    $person->getAttribute('personal_contact')
                        ?: $person->getAttribute('company_contact')
                ),
                'address' => $this->formatAttribute($person->getAttribute('address')),
                'designation' => $this->formatAttribute($person->getAttribute('designation')),
                'joining_date' => $this->formatDate($person->getAttribute('doj')),
                default => '',
            };
        }

        return '';
    }

    protected function simAssignedPerson(Model $record): ?Model
    {
        if ($record instanceof Sims && method_exists($record, 'assignedPerson')) {
            try {
                $person = $record->assignedPerson();

                return $person instanceof Model ? $person : null;
            } catch (\Throwable) {
                // Fall through.
            }
        }

        $assignType = (string) ($record->getAttribute('assign_type') ?? '');
        if ($assignType === 'employee') {
            return $this->relatedModel($record, 'employee');
        }

        return $this->relatedModel($record, 'riders');
    }

    private function resolveBikeAssignedToSource(Model $record, string $leaf): string
    {
        $assignee = $this->bikeAssignee($record);
        if (! $assignee) {
            return '';
        }

        if ($assignee instanceof Riders) {
            return match ($leaf) {
                'name' => $this->formatAttribute($assignee->getAttribute('name')),
                'id' => $this->formatAttribute($assignee->getAttribute('rider_id') ?: $assignee->getKey()),
                'email' => $this->formatAttribute($assignee->getAttribute('email')),
                'address' => $this->formatAttribute($assignee->getAttribute('address')),
                'emirates_id' => $this->formatAttribute($assignee->getAttribute('emirate_id')),
                'contact_no' => $this->formatAttribute(
                    $assignee->getAttribute('personal_contact')
                        ?: $assignee->getAttribute('company_contact')
                ),
                default => '',
            };
        }

        if ($assignee instanceof BikeRentCompany) {
            return match ($leaf) {
                'name' => $this->formatAttribute($assignee->getAttribute('name')),
                'id' => $this->formatAttribute($assignee->getKey()),
                'email' => $this->formatAttribute($assignee->getAttribute('email')),
                'address' => $this->formatAttribute($assignee->getAttribute('address')),
                'emirates_id' => $this->formatAttribute($assignee->getAttribute('emirates_id')),
                'contact_no' => $this->formatAttribute($assignee->getAttribute('company_contact')),
                default => '',
            };
        }

        return '';
    }

    /**
     * Prefer the assigned rider; otherwise the rental/garage customer (same as bike UI).
     */
    protected function bikeAssignee(Model $record): ?Model
    {
        $rider = $this->relatedModel($record, 'rider');
        if ($rider instanceof Riders) {
            return $rider;
        }

        $company = $this->relatedModel($record, 'rentalCompany');

        return $company instanceof BikeRentCompany ? $company : null;
    }

    protected function relatedModel(Model $record, string $relation): ?Model
    {
        try {
            if ($record->relationLoaded($relation)) {
                $loaded = $record->getRelation($relation);

                return $loaded instanceof Model ? $loaded : null;
            }

            if (method_exists($record, $relation)) {
                $value = $record->{$relation}();
                if ($value instanceof Relation) {
                    $related = $value->getResults();

                    return $related instanceof Model ? $related : null;
                }
            }
        } catch (\Throwable) {
            // Fall through.
        }

        return null;
    }

    private function resolveRiderOtherSource(Model $record, string $leaf): string
    {
        if (str_starts_with($leaf, 'vehicle_')) {
            $bike = $this->assignedBikeForRider($record);
            if (! $bike) {
                return '';
            }

            return match ($leaf) {
                'vehicle_plate' => $this->formatBikeCodePlateEmirates($bike),
                'vehicle_chassis' => $this->formatAttribute($bike->getAttribute('chassis_number')),
                'vehicle_engine' => $this->formatAttribute($bike->getAttribute('engine')),
                'vehicle_color' => $this->formatAttribute($bike->getAttribute('color')),
                default => '',
            };
        }

        if (str_starts_with($leaf, 'fuelCard_')) {
            $card = $this->assignedFuelCardForRider($record);
            if (! $card) {
                return '';
            }

            return match ($leaf) {
                'fuelCard_no' => $this->formatAttribute($card->getAttribute('card_number')),
                'fuelCard_monthly_charges' => $this->formatAttribute($card->getAttribute('service_charges')),
                'fuelCard_monthly_limit' => $this->formatAttribute($card->getAttribute('monthly_limit')),
                default => '',
            };
        }

        return '';
    }

    protected function assignedBikeForRider(Model $record): ?Bikes
    {
        try {
            if ($record->relationLoaded('bikes')) {
                $loaded = $record->getRelation('bikes');

                return $loaded instanceof Bikes ? $loaded : null;
            }

            if (method_exists($record, 'bikes')) {
                $bike = $record->bikes;

                return $bike instanceof Bikes ? $bike : null;
            }
        } catch (\Throwable) {
            // Fall through.
        }

        return null;
    }

    protected function assignedFuelCardForRider(Model $record): ?FuelCards
    {
        $riderId = $record->getAttribute('id');
        if ($riderId === null || $riderId === '') {
            return null;
        }

        try {
            $card = FuelCards::query()
                ->where('assigned_to', $riderId)
                ->orderByDesc('id')
                ->first();

            return $card instanceof FuelCards ? $card : null;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Format: bike_code-plate (emirates)
     */
    private function formatBikeCodePlateEmirates(Bikes $bike): string
    {
        $code = trim((string) ($bike->bike_code ?? ''));
        $plate = trim((string) ($bike->plate ?? ''));
        $emirates = trim((string) ($bike->emirates ?? ''));

        $base = match (true) {
            $code !== '' && $plate !== '' => $code.'-'.$plate,
            $code !== '' => $code,
            $plate !== '' => $plate,
            default => '',
        };

        if ($emirates === '') {
            return $base;
        }

        return $base !== '' ? $base.' ('.$emirates.')' : $emirates;
    }

    private function companyName(): string
    {
        $company = $this->resolveCompany();

        return (string) ($company?->name ?? config('app.name'));
    }

    private function companyContact(): string
    {
        $company = $this->resolveCompany();
        if (! $company) {
            return '';
        }

        return $this->formatAttribute($company->phone ?: $company->email);
    }

    private function companyAddress(): string
    {
        $company = $this->resolveCompany();
        if (! $company) {
            return '';
        }

        $parts = array_filter([
            trim((string) ($company->address ?? '')),
            trim((string) ($company->city ?? '')),
            trim((string) ($company->country ?? '')),
        ], static fn (string $part): bool => $part !== '');

        return implode(', ', $parts);
    }

    private function resolveCompany(): ?Company
    {
        $company = request()?->attributes->get('company') ?? Company::find(CompanyContext::id());

        return $company instanceof Company ? $company : null;
    }

    private function formatAttribute(mixed $attr): string
    {
        if ($attr === null || $attr === '') {
            return '';
        }

        if ($attr instanceof \DateTimeInterface) {
            return $this->formatDate($attr);
        }

        return is_scalar($attr) ? (string) $attr : '';
    }

    public function isLeftToRightPlaceholder(string $token): bool
    {
        $key = strtolower(trim($token, "{} \t\n\r"));

        return $key !== '' && preg_match(
            '/(phone|mobile|plate|chassis|vin|cnic|emirates|passport|email|iban|licen[cs]e|regist|code|number|_id|^id$)/',
            $key
        ) === 1;
    }

    public function replace(string $html, array $map): string
    {
        foreach ($map as $token => $value) {
            $token = (string) $token;
            if ($token === '') {
                continue;
            }
            $text = (string) $value;
            if ($this->isLeftToRightPlaceholder($token) && $text !== '') {
                $escaped = htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                $quoted = preg_quote($token, '/');
                // Use ${1}/${2} so digit-leading values (e.g. plate 21652) are not
                // parsed as backrefs like $12 (which drops the first digit).
                $html = preg_replace(
                    '/(<span\b[^>]*\bclass="[^"]*\bfield-value\b[^"]*"[^>]*>)\s*'.$quoted.'\s*(<\/span>)/i',
                    '${1}'.$escaped.'${2}',
                    $html
                ) ?? $html;
                $html = str_replace(
                    $token,
                    '<span dir="ltr" class="field-value">'.$escaped.'</span>',
                    $html
                );
                continue;
            }
            $html = str_replace($token, $text, $html);
        }

        return $html;
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
