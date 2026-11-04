<?php

namespace App\Services\ExcelImport;

use App\Models\ExcelImportMapping;
use App\Models\Items;
use App\Models\SimCompany;
use App\Support\CompanyContext;
use App\Support\ExcelImport\ExcelImportMappingRegistry;
use App\Support\ExcelImport\ExcelImportModuleProfile;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class ExcelImportMappingService
{
    public function profile(string $moduleKey): ExcelImportModuleProfile
    {
        return ExcelImportMappingRegistry::get($moduleKey);
    }

    /**
     * @return array{configured:bool,header_rows_to_skip:int,column_mappings:array<string,int>,dynamic_mappings:list<array<string,mixed>>,options:array}
     */
    public function resolve(string $moduleKey, ?int $scopeId = null, ?string $importType = null): array
    {
        $profile = $this->profile($moduleKey);
        $importType = $importType ?: $profile->importType;

        $defaults = [
            'configured' => false,
            'header_rows_to_skip' => $profile->defaultHeaderRowsToSkip,
            'column_mappings' => array_fill_keys($profile->fieldKeys(), null),
            'dynamic_mappings' => [],
            'options' => [],
        ];

        $stored = $this->findStored($moduleKey, $scopeId, $importType);
        if (! $stored) {
            return $defaults;
        }

        return [
            'configured' => true,
            'header_rows_to_skip' => max(0, (int) $stored->header_rows_to_skip),
            'column_mappings' => $this->sanitizeColumnMappings($profile, (array) ($stored->column_mappings ?? [])),
            'dynamic_mappings' => $this->sanitizeDynamicMappings($profile, (array) ($stored->dynamic_mappings ?? [])),
            'options' => is_array($stored->options) ? $stored->options : [],
        ];
    }

    /**
     * Payload shaped for the SIM invoice import form JS.
     *
     * @return array{configured:bool,header_rows_to_skip:int,sim_number:?int,items:list<array{item_id:int,col:int,rate:float}>}
     */
    public function formPayloadForSimInvoice(?int $simCompanyId): array
    {
        $resolved = $this->resolve(ExcelImportMappingRegistry::MODULE_SIM_INVOICES, $simCompanyId);
        if (! $resolved['configured']) {
            return [
                'configured' => false,
                'header_rows_to_skip' => (int) $resolved['header_rows_to_skip'],
                'sim_number' => null,
                'items' => [],
            ];
        }

        $items = [];
        foreach ($resolved['dynamic_mappings'] as $row) {
            $items[] = [
                'item_id' => (int) $row['item_id'],
                'col' => (int) $row['col'],
                'rate' => (float) $row['rate'],
            ];
        }

        return [
            'configured' => true,
            'header_rows_to_skip' => (int) $resolved['header_rows_to_skip'],
            'sim_number' => isset($resolved['column_mappings']['sim_number'])
                ? (int) $resolved['column_mappings']['sim_number']
                : null,
            'items' => $items,
        ];
    }

    /**
     * @return array<int, array{configured:bool,header_rows_to_skip:int,sim_number:?int,items:list<array{item_id:int,col:int,rate:float}>}>
     */
    public function formPayloadBySimCompany(): array
    {
        $payload = [];
        $companyIds = SimCompany::query()->where('status', 1)->pluck('id');
        foreach ($companyIds as $id) {
            $id = (int) $id;
            $row = $this->formPayloadForSimInvoice($id);
            if ($row['configured']) {
                $payload[$id] = $row;
            }
        }

        return $payload;
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public function save(string $moduleKey, array $input, ?int $scopeId = null, ?string $importType = null): ExcelImportMapping
    {
        $profile = $this->profile($moduleKey);
        $importType = $importType ?: $profile->importType;

        if ($profile->hasScope() && empty($scopeId)) {
            throw ValidationException::withMessages([
                'scope_id' => 'Please select a ' . strtolower((string) ($profile->scope['label'] ?? 'scope')) . '.',
            ]);
        }

        $columnMappings = $this->sanitizeColumnMappings($profile, (array) ($input['column_mappings'] ?? []));
        foreach ($profile->requiredFieldKeys() as $key) {
            if (empty($columnMappings[$key])) {
                throw ValidationException::withMessages([
                    "column_mappings.{$key}" => 'Please map ' . $this->fieldLabel($profile, $key) . '.',
                ]);
            }
        }

        $dynamicMappings = $this->sanitizeDynamicMappings($profile, (array) ($input['dynamic_mappings'] ?? []));
        if ($profile->supportsDynamicMappings() && $dynamicMappings === []) {
            throw ValidationException::withMessages([
                'dynamic_mappings' => 'Add at least one item column mapping.',
            ]);
        }

        if ($profile->supportsDynamicMappings() && ($profile->dynamic['source'] ?? '') === 'sim_items') {
            $validIds = Items::whereIn('id', array_column($dynamicMappings, 'item_id'))
                ->where('status', 1)
                ->whereJsonContains('owner', 'sim')
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();
            if (count($validIds) !== count($dynamicMappings)) {
                throw ValidationException::withMessages([
                    'dynamic_mappings' => 'One or more mapped items are invalid or not assigned to the SIM module.',
                ]);
            }
        }

        $this->assertUniqueColumns($columnMappings, $dynamicMappings);

        return ExcelImportMapping::updateOrCreate(
            [
                'company_id' => CompanyContext::id(),
                'module_key' => $moduleKey,
                'import_type' => $importType,
                'scope_type' => $profile->scopeType(),
                'scope_id' => $profile->hasScope() ? (int) $scopeId : null,
            ],
            [
                'header_rows_to_skip' => max(0, (int) ($input['header_rows_to_skip'] ?? $profile->defaultHeaderRowsToSkip)),
                'column_mappings' => $columnMappings,
                'dynamic_mappings' => $dynamicMappings,
                'required_fields' => array_fill_keys($profile->requiredFieldKeys(), true),
                'options' => is_array($input['options'] ?? null) ? $input['options'] : [],
                'is_active' => array_key_exists('is_active', $input)
                    ? filter_var($input['is_active'], FILTER_VALIDATE_BOOLEAN)
                    : true,
            ]
        );
    }

    public function findStored(string $moduleKey, ?int $scopeId = null, ?string $importType = null): ?ExcelImportMapping
    {
        $profile = $this->profile($moduleKey);
        $importType = $importType ?: $profile->importType;

        $query = ExcelImportMapping::query()
            ->where('module_key', $moduleKey)
            ->where('import_type', $importType)
            ->where('is_active', true);

        if ($profile->hasScope()) {
            $query->where('scope_type', $profile->scopeType())
                ->where('scope_id', (int) $scopeId);
        } else {
            $query->whereNull('scope_type')->whereNull('scope_id');
        }

        return $query->first();
    }

    /**
     * @return Collection<int, object>
     */
    public function scopeOptions(ExcelImportModuleProfile $profile): Collection
    {
        if (! $profile->hasScope()) {
            return collect();
        }

        return match ($profile->scopeType()) {
            'sim_company' => SimCompany::query()
                ->where('status', 1)
                ->orderBy('name')
                ->get(['id', 'name']),
            default => collect(),
        };
    }

    /**
     * @return list<array{id:int,name:string,price:float,vat:float}>
     */
    public function dynamicItemOptions(ExcelImportModuleProfile $profile): array
    {
        if (! $profile->supportsDynamicMappings()) {
            return [];
        }

        $source = (string) ($profile->dynamic['source'] ?? '');

        return match ($source) {
            'sim_items' => Items::dropdown('sim')->map(fn ($item) => [
                'id' => (int) $item->id,
                'name' => (string) $item->name,
                'price' => (float) ($item->price ?? 0),
                'vat' => (float) ($item->vat ?? 0),
            ])->values()->all(),
            default => [],
        };
    }

    /**
     * @return array<int, string>
     */
    public function excelColumnChoices(int $maxIndex): array
    {
        $choices = [];
        for ($i = 1; $i <= max(1, $maxIndex); $i++) {
            $choices[$i] = $this->columnIndexToLetter($i) . ' (col ' . $i . ')';
        }

        return $choices;
    }

    public function columnIndexToLetter(int $index): string
    {
        $index = max(1, $index);
        $letter = '';
        $n = $index;
        while ($n > 0) {
            $n--;
            $letter = chr(65 + ($n % 26)) . $letter;
            $n = intdiv($n, 26);
        }

        return $letter;
    }

    /**
     * @param  array<string, mixed>  $mappings
     * @return array<string, int>
     */
    public function sanitizeColumnMappings(ExcelImportModuleProfile $profile, array $mappings): array
    {
        $clean = [];
        foreach ($profile->fieldKeys() as $key) {
            $value = $mappings[$key] ?? null;
            if ($value === null || $value === '') {
                continue;
            }
            $col = (int) $value;
            if ($col >= 1 && $col <= $profile->maxColumnIndex) {
                $clean[$key] = $col;
            }
        }

        return $clean;
    }

    /**
     * @param  array<int, mixed>  $rows
     * @return list<array{item_id:int,col:int,rate:float}>
     */
    public function sanitizeDynamicMappings(ExcelImportModuleProfile $profile, array $rows): array
    {
        if (! $profile->supportsDynamicMappings()) {
            return [];
        }

        $clean = [];
        $seenItems = [];
        $seenCols = [];

        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }
            $itemId = (int) ($row['item_id'] ?? 0);
            $col = (int) ($row['col'] ?? 0);
            if ($itemId < 1 || $col < 1 || $col > $profile->maxColumnIndex) {
                continue;
            }
            if (isset($seenItems[$itemId]) || isset($seenCols[$col])) {
                continue;
            }
            $rate = isset($row['rate']) && $row['rate'] !== '' ? (float) $row['rate'] : 0.0;
            $seenItems[$itemId] = true;
            $seenCols[$col] = true;
            $clean[] = [
                'item_id' => $itemId,
                'col' => $col,
                'rate' => $rate,
            ];
        }

        return $clean;
    }

    private function fieldLabel(ExcelImportModuleProfile $profile, string $key): string
    {
        foreach ($profile->fields as $field) {
            if ($field['key'] === $key) {
                return (string) $field['label'];
            }
        }

        return $key;
    }

    /**
     * @param  array<string, int>  $columnMappings
     * @param  list<array{item_id:int,col:int,rate:float}>  $dynamicMappings
     */
    private function assertUniqueColumns(array $columnMappings, array $dynamicMappings): void
    {
        $used = [];
        foreach ($columnMappings as $col) {
            if (isset($used[$col])) {
                throw ValidationException::withMessages([
                    'column_mappings' => 'Column numbers must be unique.',
                ]);
            }
            $used[$col] = true;
        }
        foreach ($dynamicMappings as $row) {
            $col = (int) $row['col'];
            if (isset($used[$col])) {
                throw ValidationException::withMessages([
                    'dynamic_mappings' => 'Column numbers must be unique across fields and items.',
                ]);
            }
            $used[$col] = true;
        }
    }
}
