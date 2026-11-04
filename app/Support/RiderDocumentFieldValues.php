<?php

namespace App\Support;

use App\Helpers\Common;
use App\Models\Accounts;
use App\Models\Branch;
use App\Models\Countries;
use App\Models\Customers;
use App\Models\RiderCustomField;
use App\Models\RiderDocumentType;
use App\Models\RiderDocumentTypeField;
use App\Models\RiderDocumentTypeSubtype;
use App\Models\RiderFieldCategoryAssignment;
use App\Models\Riders;
use App\Models\Vendors;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class RiderDocumentFieldValues
{
    /**
     * @return array{document_type_id: ?int, document_subtype_id: ?int, field_values: array<string, mixed>}
     */
    public static function validateAndNormalize(
        ?int $documentTypeId,
        ?int $documentSubtypeId,
        mixed $rawFieldValues,
        bool $requireTypeWhenSubtypeConfigured = true
    ): array {
        $fieldValues = is_array($rawFieldValues) ? $rawFieldValues : [];

        if (!$documentTypeId && !$documentSubtypeId) {
            return [
                'document_type_id' => null,
                'document_subtype_id' => null,
                'field_values' => [],
            ];
        }

        $type = $documentTypeId
            ? RiderDocumentType::query()->where('is_active', true)->find($documentTypeId)
            : null;

        if ($documentTypeId && !$type) {
            throw ValidationException::withMessages([
                'document_type_id' => 'Selected document type is invalid or inactive.',
            ]);
        }

        $subtype = null;
        if ($documentSubtypeId) {
            $subtype = RiderDocumentTypeSubtype::query()
                ->where('is_active', true)
                ->find($documentSubtypeId);

            if (!$subtype) {
                throw ValidationException::withMessages([
                    'document_subtype_id' => 'Selected document subtype is invalid or inactive.',
                ]);
            }

            if ($type && (int) $subtype->document_type_id !== (int) $type->id) {
                throw ValidationException::withMessages([
                    'document_subtype_id' => 'Subtype does not belong to the selected document type.',
                ]);
            }

            if (!$type) {
                $type = RiderDocumentType::query()->where('is_active', true)->find($subtype->document_type_id);
            }
        }

        if ($requireTypeWhenSubtypeConfigured && $type) {
            $hasSubtypes = RiderDocumentTypeSubtype::query()
                ->where('document_type_id', $type->id)
                ->where('is_active', true)
                ->exists();

            if ($hasSubtypes && !$subtype) {
                throw ValidationException::withMessages([
                    'document_subtype_id' => 'Please select a document subtype.',
                ]);
            }
        }

        $normalized = [];
        if ($subtype) {
            $fields = RiderDocumentTypeField::query()
                ->where('subtype_id', $subtype->id)
                ->where('is_active', true)
                ->orderedForAdmin()
                ->get();

            $errors = [];
            foreach ($fields as $field) {
                $value = $fieldValues[$field->key] ?? null;
                if (is_string($value)) {
                    $value = trim($value);
                }
                if ($value === '' || $value === null) {
                    $value = null;
                    if ($field->is_required) {
                        $errors['field_values.' . $field->key] = $field->label . ' is required.';
                    }
                } else {
                    $typeError = self::validateInputType($field, $value);
                    if ($typeError !== null) {
                        $errors['field_values.' . $field->key] = $typeError;
                    }
                }
                $normalized[$field->key] = $value;
            }

            if ($errors !== []) {
                throw ValidationException::withMessages($errors);
            }
        }

        return [
            'document_type_id' => $type?->id,
            'document_subtype_id' => $subtype?->id,
            'field_values' => $normalized,
        ];
    }

    /**
     * Sync linked rider profile columns from this document's field values only.
     *
     * @param  array<string, mixed>  $fieldValues
     */
    public static function syncLinkedRiderColumns(
        Riders $rider,
        ?int $documentSubtypeId,
        array $fieldValues
    ): void {
        if (!$documentSubtypeId || $fieldValues === []) {
            return;
        }

        $fields = RiderDocumentTypeField::query()
            ->where('subtype_id', $documentSubtypeId)
            ->where('is_active', true)
            ->whereNotNull('rider_column')
            ->where('rider_column', '!=', '')
            ->get();

        if ($fields->isEmpty()) {
            return;
        }

        $riderColumns = Schema::hasTable('riders')
            ? array_flip(Schema::getColumnListing('riders'))
            : [];

        $updates = [];
        foreach ($fields as $field) {
            $column = (string) $field->rider_column;
            if ($column === '' || !isset($riderColumns[$column])) {
                continue;
            }
            if (!array_key_exists($field->key, $fieldValues)) {
                continue;
            }
            $updates[$column] = self::normalizeValueForRiderColumn($column, $fieldValues[$field->key]);
        }

        if ($updates !== []) {
            $rider->fill($updates);
            $rider->save();
        }
    }

    /**
     * Prefill field values from linked rider columns (for create/upload).
     *
     * @return array<string, mixed>
     */
    public static function prefillFromRider(?Riders $rider, ?int $documentSubtypeId): array
    {
        if (!$rider || !$documentSubtypeId) {
            return [];
        }

        $fields = RiderDocumentTypeField::query()
            ->where('subtype_id', $documentSubtypeId)
            ->where('is_active', true)
            ->whereNotNull('rider_column')
            ->where('rider_column', '!=', '')
            ->orderedForAdmin()
            ->get();

        $values = [];
        foreach ($fields as $field) {
            $column = (string) $field->rider_column;
            $raw = $rider->getAttribute($column);
            if ($raw === null || $raw === '') {
                continue;
            }
            $values[$field->key] = self::formatRiderValueForField($column, $raw, $field, $rider);
        }

        return $values;
    }

    /**
     * Resolve files.expiry_date from the first date field linked to a known expiry rider column.
     *
     * @param  array<string, mixed>  $fieldValues
     */
    public static function resolveExpiryDateFromFields(?int $documentSubtypeId, array $fieldValues): ?string
    {
        if (!$documentSubtypeId || $fieldValues === []) {
            return null;
        }

        $expiryColumns = array_values(array_filter(array_map(
            fn ($map) => $map['expiry'] ?? null,
            \App\Support\RiderDocumentReplacement::fieldMap()
        )));

        $fields = RiderDocumentTypeField::query()
            ->where('subtype_id', $documentSubtypeId)
            ->where('is_active', true)
            ->orderedForAdmin()
            ->get();

        foreach ($fields as $field) {
            $value = $fieldValues[$field->key] ?? null;
            if ($value === null || $value === '') {
                continue;
            }
            $isExpiry = ($field->input_type === 'date' && $field->rider_column && in_array($field->rider_column, $expiryColumns, true))
                || str_contains(strtolower($field->key), 'expir')
                || str_contains(strtolower((string) $field->label), 'expir');
            if (!$isExpiry) {
                continue;
            }
            try {
                return Carbon::parse((string) $value)->format('Y-m-d');
            } catch (\Throwable) {
                continue;
            }
        }

        return null;
    }

    /**
     * Resolve document type/subtype ids from missing-doc key (e.g. passport_front, emirates_back, road).
     *
     * @return array{document_type_id: ?int, document_subtype_id: ?int, type_key: ?string, subtype_key: ?string}
     */
    public static function resolveTypeFromMissingKey(string $missingKey): array
    {
        $missingKey = strtolower(trim($missingKey));
        $subtypeKey = null;
        $typeKey = $missingKey;

        if (str_ends_with($missingKey, '_front')) {
            $typeKey = substr($missingKey, 0, -6);
            $subtypeKey = 'front';
        } elseif (str_ends_with($missingKey, '_back')) {
            $typeKey = substr($missingKey, 0, -5);
            $subtypeKey = 'back';
        }

        $type = RiderDocumentType::query()->where('key', $typeKey)->where('is_active', true)->first();
        if (!$type) {
            return [
                'document_type_id' => null,
                'document_subtype_id' => null,
                'type_key' => $typeKey,
                'subtype_key' => $subtypeKey,
            ];
        }

        $subtypeId = null;
        if ($subtypeKey) {
            $subtypeId = RiderDocumentTypeSubtype::query()
                ->where('document_type_id', $type->id)
                ->where('key', $subtypeKey)
                ->where('is_active', true)
                ->value('id');
        } else {
            // Prefer "main" for single types, else first active subtype
            $subtypeId = RiderDocumentTypeSubtype::query()
                ->where('document_type_id', $type->id)
                ->where('is_active', true)
                ->where('key', 'main')
                ->value('id');
            if (!$subtypeId) {
                $subtypeId = RiderDocumentTypeSubtype::query()
                    ->where('document_type_id', $type->id)
                    ->where('is_active', true)
                    ->orderedForAdmin()
                    ->value('id');
            }
        }

        return [
            'document_type_id' => $type->id,
            'document_subtype_id' => $subtypeId ? (int) $subtypeId : null,
            'type_key' => $typeKey,
            'subtype_key' => $subtypeKey,
        ];
    }

    /**
     * Suggested file name fragment for missing-doc matching.
     */
    public static function suggestedNameFragment(?RiderDocumentType $type, ?RiderDocumentTypeSubtype $subtype): ?string
    {
        if (!$type) {
            return null;
        }
        $key = $type->key;
        if ($subtype && in_array($subtype->key, ['front', 'back'], true)) {
            return $key . '_' . $subtype->key;
        }
        if ($subtype && $subtype->key !== 'main') {
            return $key . '_' . $subtype->key;
        }

        return $key;
    }

    private static function formatRiderValueForField(string $column, mixed $raw, RiderDocumentTypeField $field, Riders $rider): mixed
    {
        if ($column === 'nationality') {
            if (is_numeric($raw)) {
                $name = Countries::query()->whereKey((int) $raw)->value('name');
                if (!$name && Schema::hasColumn('countries', 'country')) {
                    $name = Countries::query()->whereKey((int) $raw)->value('country');
                }
                if ($name) {
                    return $name;
                }
            }
            if ($rider->relationLoaded('nationality') || method_exists($rider, 'nationality')) {
                try {
                    $rel = $rider->nationality;
                    if ($rel && !empty($rel->name ?? $rel->country ?? null)) {
                        return $rel->name ?? $rel->country;
                    }
                } catch (\Throwable) {
                    // ignore
                }
            }
        }

        if ($field->input_type === 'date' || str_contains($column, 'expir') || $column === 'dob') {
            try {
                return Carbon::parse((string) $raw)->format('Y-m-d');
            } catch (\Throwable) {
                return $raw;
            }
        }

        return $raw;
    }

    private static function normalizeValueForRiderColumn(string $column, mixed $value): mixed
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($column === 'nationality' && !is_numeric($value) && Schema::hasTable('countries')) {
            $q = Countries::query()->where('name', $value);
            if (Schema::hasColumn('countries', 'country')) {
                $q->orWhere('country', $value);
            }
            $countryId = $q->value('id');
            if ($countryId) {
                return $countryId;
            }
        }

        return $value;
    }

    private static function validateInputType(RiderDocumentTypeField $field, mixed $value): ?string
    {
        $stringValue = is_scalar($value) ? (string) $value : '';

        return match ($field->input_type) {
            'number' => is_numeric($stringValue) ? null : $field->label . ' must be a number.',
            'date' => preg_match('/^\d{4}-\d{2}-\d{2}$/', $stringValue)
                ? null
                : $field->label . ' must be a valid date (YYYY-MM-DD).',
            'select' => self::validateSelectOption($field, $stringValue),
            default => null,
        };
    }

    private static function validateSelectOption(RiderDocumentTypeField $field, string $value): ?string
    {
        $presentation = self::presentationForDocumentField($field);
        $selectOptions = $presentation['select_options'] ?? [];
        if ($selectOptions !== []) {
            $allowed = array_map('strval', array_keys($selectOptions));
            // Also allow matching by option label (legacy stored names).
            foreach ($selectOptions as $optValue => $optLabel) {
                $allowed[] = (string) $optLabel;
            }
            $allowed = array_values(array_unique(array_filter($allowed, fn ($v) => $v !== '')));
            if ($allowed !== [] && !in_array($value, $allowed, true)) {
                return $field->label . ' has an invalid option.';
            }

            return null;
        }

        $options = is_array($field->options) ? $field->options : [];
        $allowed = [];
        foreach ($options as $opt) {
            if (is_array($opt)) {
                $allowed[] = (string) ($opt['value'] ?? $opt['label'] ?? '');
            } else {
                $allowed[] = (string) $opt;
            }
        }
        $allowed = array_values(array_filter($allowed, fn ($v) => $v !== ''));
        if ($allowed !== [] && !in_array($value, $allowed, true)) {
            return $field->label . ' has an invalid option.';
        }

        return null;
    }

    /**
     * Resolve how a document field should render, mirroring Rider module configuration
     * when the field is linked to a rider column.
     *
     * @return array{
     *   label: string,
     *   type: string,
     *   required: bool,
     *   maxlength: ?int,
     *   placeholder: ?string,
     *   rows: ?int,
     *   select_options: array<string|int, string>,
     *   use_select2: bool,
     *   from_rider: bool
     * }
     */
    public static function presentationForDocumentField(RiderDocumentTypeField $field): array
    {
        $label = (string) $field->label;
        $type = (string) ($field->input_type ?: 'text');
        $required = (bool) $field->is_required;
        $maxlength = null;
        $placeholder = null;
        $rows = $type === 'textarea' ? 2 : null;
        $selectOptions = [];
        $useSelect2 = false;
        $fromRider = false;

        $riderKey = trim((string) ($field->rider_column ?: ''));
        if ($riderKey === '') {
            $riderKey = trim((string) ($field->key ?: ''));
        }

        $spec = self::resolveRiderFieldSpec($riderKey);
        if ($spec !== null) {
            $fromRider = true;
            $type = (string) ($spec['type'] ?? $type);
            if ($type === 'dropdown') {
                $type = 'select';
            }
            $maxlength = isset($spec['maxlength']) ? (int) $spec['maxlength'] : null;
            $placeholder = isset($spec['placeholder']) ? (string) $spec['placeholder'] : null;
            $rows = isset($spec['rows']) ? (int) $spec['rows'] : $rows;
            if (!empty($spec['label'])) {
                $label = (string) $spec['label'];
            }
            if ($type === 'select') {
                $selectOptions = self::resolveRiderSelectOptions($spec);
                $useSelect2 = true;
            }
        } elseif ($type === 'select') {
            foreach ((array) ($field->options ?? []) as $opt) {
                if (is_array($opt)) {
                    $ov = (string) ($opt['value'] ?? $opt['label'] ?? '');
                    $ol = (string) ($opt['label'] ?? $opt['value'] ?? '');
                } else {
                    $ov = (string) $opt;
                    $ol = (string) $opt;
                }
                if ($ov !== '') {
                    $selectOptions[$ov] = $ol !== '' ? $ol : $ov;
                }
            }
            $useSelect2 = $selectOptions !== [];
        }

        return [
            'label' => $label,
            'type' => $type,
            'required' => $required,
            'maxlength' => $maxlength,
            'placeholder' => $placeholder,
            'rows' => $rows,
            'select_options' => $selectOptions,
            'use_select2' => $useSelect2,
            'from_rider' => $fromRider,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function resolveRiderFieldSpec(string $fieldKey): ?array
    {
        $fieldKey = trim($fieldKey);
        if ($fieldKey === '') {
            return null;
        }

        $specs = RiderCustomField::fixedFieldInputSpecs();
        $base = $specs[$fieldKey] ?? null;
        $spec = ModuleFieldSource::mergeFixedFieldSpec($fieldKey, $base);

        $assignment = null;
        if (Schema::hasTable('rider_field_category_assignments')) {
            $assignment = RiderFieldCategoryAssignment::query()
                ->where('field_key', $fieldKey)
                ->orderBy('id')
                ->first();
        }

        if ($assignment) {
            if ($assignment->display_label !== null && trim((string) $assignment->display_label) !== '') {
                $spec['label'] = trim((string) $assignment->display_label);
            } else {
                $spec['label'] = RiderCustomField::humanizeFieldKey($fieldKey);
            }
            if (!empty($assignment->input_type)) {
                $spec['type'] = $assignment->input_type === 'dropdown' ? 'select' : $assignment->input_type;
            }
            if (is_array($assignment->input_config) && array_key_exists('options', $assignment->input_config)) {
                $spec['options'] = $assignment->input_config['options'];
            }
        } elseif ($base !== null) {
            $spec['label'] = RiderCustomField::humanizeFieldKey($fieldKey);
        } else {
            return null;
        }

        // If assignment exists but type never set, keep merged base type.
        if (empty($spec['type'])) {
            $spec['type'] = 'text';
        }

        return $spec;
    }

    /**
     * Build select options exactly like riders/_form_field.blade.php.
     *
     * @param  array<string, mixed>  $spec
     * @return array<string|int, string>
     */
    public static function resolveRiderSelectOptions(array $spec): array
    {
        $opts = [];
        $rawOptions = $spec['options'] ?? null;
        $parsedOptions = [];
        if ($rawOptions !== null && $rawOptions !== '') {
            $lines = is_array($rawOptions) ? $rawOptions : preg_split('/\r\n|\r|\n/', (string) $rawOptions);
            foreach ($lines as $line) {
                $line = trim((string) $line);
                if ($line !== '') {
                    $parsedOptions[] = $line;
                }
            }
        }

        $dropdown = (string) ($spec['dropdown'] ?? '');
        // Nationality always uses the full countries table (ignore custom option overrides).
        if ($dropdown === 'countries') {
            return Countries::list()->toArray();
        }

        if ($parsedOptions !== []) {
            foreach ($parsedOptions as $opt) {
                $opts[$opt] = $opt;
            }

            return $opts;
        }
        if ($dropdown === 'vendors') {
            return Vendors::dropdown() ?: [];
        }
        if ($dropdown === 'recruiters') {
            $opts = ['' => 'Select Recruiter'];
            foreach (company_table('recruiters')->where('status', 1)->get() as $r) {
                $opts[$r->id] = $r->name;
            }

            return $opts;
        }
        if ($dropdown === 'accounts') {
            return Accounts::dropdown(null) ?? ['' => 'Select'];
        }
        if ($dropdown === 'customers') {
            return Customers::pluck('name', 'id')->prepend('Select', '')->toArray();
        }
        if ($dropdown === 'branch') {
            $opts = ['' => 'Select'];
            foreach (Branch::active()->orderBy('name')->get(['id', 'name', 'code']) as $branch) {
                $opts[$branch->id] = trim($branch->name . ($branch->code ? ' (' . $branch->code . ')' : ''));
            }

            return $opts;
        }
        if ($dropdown !== '') {
            return Common::Dropdowns($dropdown) ?: [];
        }

        return [];
    }
}
