<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;

class EntityExpiry
{
    public static function isExpiryKey(string $key): bool
    {
        return (bool) preg_match('/expir/i', $key);
    }

    /**
     * @return array{status: string, label: string, class: string, url: ?string, expiry: ?string, text: string, name: string}|null
     */
    public static function badgeForDate(mixed $dateValue, string $name = 'Document', ?string $url = null): ?array
    {
        if ($dateValue === null || $dateValue === '') {
            return null;
        }

        try {
            $normalized = Carbon::parse($dateValue)->toDateString();
        } catch (\Throwable $e) {
            return null;
        }

        $status = RiderDocumentReplacement::expiryStatus($normalized);

        return [
            'status' => $status['status'],
            'label' => $status['label'],
            'class' => $status['class'],
            'url' => $url,
            'expiry' => $normalized,
            'text' => Carbon::parse($normalized)->format('d M Y'),
            'name' => $name,
        ];
    }

    /**
     * @return array{expired: int, expiring: int}
     */
    public static function fileCounts(string $type, int $typeId, int $days = 30): array
    {
        $empty = ['expired' => 0, 'expiring' => 0];
        if ($type === '' || $typeId < 1 || ! Schema::hasTable('files')) {
            return $empty;
        }

        $today = now()->startOfDay()->toDateString();
        $end = now()->addDays($days)->toDateString();

        try {
            $base = CompanyQuery::table('files')
                ->where('type', $type)
                ->where('type_id', $typeId)
                ->whereNotNull('expiry_date');

            return [
                'expired' => (int) (clone $base)->whereDate('expiry_date', '<', $today)->count(),
                'expiring' => (int) (clone $base)->whereDate('expiry_date', '>=', $today)->whereDate('expiry_date', '<=', $end)->count(),
            ];
        } catch (\Throwable $e) {
            return $empty;
        }
    }

    /**
     * @param  array<int, string>  $fields
     * @return array{expired: int, expiring: int}
     */
    public static function fieldCounts(mixed $model, array $fields, int $days = 30): array
    {
        $expired = 0;
        $expiring = 0;
        $today = now()->startOfDay();
        $end = now()->addDays($days)->endOfDay();

        foreach ($fields as $field) {
            $value = is_object($model) ? data_get($model, $field) : null;
            if ($value === null || $value === '') {
                continue;
            }
            try {
                $date = Carbon::parse($value)->startOfDay();
            } catch (\Throwable $e) {
                continue;
            }
            if ($date->lt($today)) {
                $expired++;
            } elseif ($date->lte($end)) {
                $expiring++;
            }
        }

        return ['expired' => $expired, 'expiring' => $expiring];
    }

    /**
     * @return list<string>
     */
    public static function expiryFieldsFromModel(mixed $model): array
    {
        if (! is_object($model) || ! method_exists($model, 'getFillable')) {
            return [];
        }

        $keys = array_unique(array_merge(
            array_keys(method_exists($model, 'getAttributes') ? $model->getAttributes() : []),
            $model->getFillable()
        ));

        return array_values(array_filter($keys, fn ($key) => self::isExpiryKey((string) $key)));
    }

    /**
     * @return array{info_expired: int, info_expiring: int, files_expired: int, files_expiring: int}
     */
    public static function countsFor(string $fileType, mixed $model, int $days = 30): array
    {
        $id = (int) data_get($model, 'id', 0);
        $files = $id > 0 ? self::fileCounts($fileType, $id, $days) : ['expired' => 0, 'expiring' => 0];
        $fields = $model
            ? self::fieldCounts($model, self::expiryFieldsFromModel($model), $days)
            : ['expired' => 0, 'expiring' => 0];

        return [
            'info_expired' => $fields['expired'],
            'info_expiring' => $fields['expiring'],
            'files_expired' => $files['expired'],
            'files_expiring' => $files['expiring'],
        ];
    }
}
