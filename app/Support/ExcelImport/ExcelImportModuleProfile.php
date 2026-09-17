<?php

namespace App\Support\ExcelImport;

/**
 * Declares how one Excel-import module stores and edits column mappings.
 */
final class ExcelImportModuleProfile
{
    /**
     * @param  list<array{key:string,label:string,required?:bool}>  $fields
     * @param  array{type:string,label:string}|null  $scope
     * @param  array{enabled:bool,label?:string,source?:string}|null  $dynamic
     */
    public function __construct(
        public readonly string $key,
        public readonly string $label,
        public readonly array $fields,
        public readonly ?array $scope = null,
        public readonly ?array $dynamic = null,
        public readonly string $importType = 'default',
        public readonly int $defaultHeaderRowsToSkip = 0,
        public readonly ?string $permission = null,
        public readonly int $maxColumnIndex = 60,
    ) {
    }

    public function fieldKeys(): array
    {
        return array_values(array_map(fn (array $f) => $f['key'], $this->fields));
    }

    public function requiredFieldKeys(): array
    {
        $keys = [];
        foreach ($this->fields as $field) {
            if (! empty($field['required'])) {
                $keys[] = $field['key'];
            }
        }

        return $keys;
    }

    public function hasScope(): bool
    {
        return is_array($this->scope) && ! empty($this->scope['type']);
    }

    public function scopeType(): ?string
    {
        return $this->hasScope() ? (string) $this->scope['type'] : null;
    }

    public function supportsDynamicMappings(): bool
    {
        return is_array($this->dynamic) && ! empty($this->dynamic['enabled']);
    }
}
