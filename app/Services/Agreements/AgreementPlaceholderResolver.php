<?php

namespace App\Services\Agreements;

use App\Models\Company;
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

    private function companyName(): string
    {
        $company = request()?->attributes->get('company') ?? Company::find(CompanyContext::id());

        return (string) ($company->name ?? config('app.name'));
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

    public function replace(string $html, array $map): string
    {
        return str_replace(array_keys($map), array_values($map), $html);
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
