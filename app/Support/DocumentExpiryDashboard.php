<?php

namespace App\Support;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

class DocumentExpiryDashboard
{
    /**
     * @return array{
     *   expiring: array{total: int, by_module: list<array{label: string, count: int}>, items: list<array<string, mixed>>, list_url: string},
     *   expired: array{total: int, by_module: list<array{label: string, count: int}>, items: list<array<string, mixed>>, list_url: string}
     * }
     */
    public static function forUser(?User $user): array
    {
        $slug = (string) (request()->route('company_slug') ?? session('company_slug') ?? '');

        return [
            'expiring' => self::buildSection($user, 'expiring', $slug, 12),
            'expired' => self::buildSection($user, 'expired', $slug, 12),
        ];
    }

    /**
     * Full list for the expiry listing page (no preview limit).
     *
     * @return array{total: int, by_module: list<array{label: string, count: int}>, items: list<array<string, mixed>>, list_url: string}
     */
    public static function listSectionForUser(?User $user, string $filter): array
    {
        $slug = (string) (request()->route('company_slug') ?? session('company_slug') ?? '');

        return self::buildSection($user, $filter, $slug, null);
    }

    /**
     * @return array{total: int, by_module: list<array{label: string, count: int}>, items: list<array<string, mixed>>, list_url: string}
     */
    protected static function buildSection(?User $user, string $filter, string $companySlug, ?int $previewLimit): array
    {
        $items = self::collectItems($user, $filter);
        $byModule = $items
            ->groupBy('module_label')
            ->map(static fn (Collection $group, string $label) => [
                'label' => $label,
                'count' => $group->count(),
            ])
            ->sortByDesc('count')
            ->values()
            ->all();

        $days = DocumentExpiry::windowDays();

        $listed = $previewLimit === null ? $items : $items->take($previewLimit);

        return [
            'total' => $items->count(),
            'by_module' => $byModule,
            'items' => $listed->values()->all(),
            'list_url' => self::listUrl($filter, $companySlug, $days),
        ];
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    protected static function collectItems(?User $user, string $filter): Collection
    {
        $items = collect();

        if (Schema::hasTable('files') && Schema::hasColumn('files', 'expiry_date')) {
            $allowedTypes = self::allowedFileTypesForUser($user);
            if ($allowedTypes !== []) {
                $query = DocumentExpiry::applyFilter(
                    DocumentExpiry::baseQuery()->whereIn('type', $allowedTypes),
                    $filter
                );

                $rows = $query
                    ->orderBy('expiry_date')
                    ->orderBy('id')
                    ->get(['id', 'type', 'type_id', 'name', 'file_name', 'expiry_date']);

                foreach ($rows as $row) {
                    $type = (string) $row->type;
                    $config = self::typeConfig()[$type] ?? null;
                    $items->push(self::mapFileRow($row, $config, $type, $filter));
                }
            }
        }

        if (self::userCanAccessSource($user, 'bike_registration') && Schema::hasTable('bike_registrations')) {
            $regQuery = company_table('bike_registrations')->whereNotNull('expiry_date');
            $today = now()->startOfDay()->toDateString();
            $end = now()->addDays(DocumentExpiry::windowDays())->startOfDay()->toDateString();

            if ($filter === 'expired') {
                $regQuery->whereDate('expiry_date', '<', $today);
            } else {
                $regQuery->whereDate('expiry_date', '>=', $today)
                    ->whereDate('expiry_date', '<=', $end);
            }

            $config = self::typeConfig()['bike_registration'];
            foreach ($regQuery->orderBy('expiry_date')->get(['id', 'expiry_date', 'detail', 'trans_code', 'reference_number', 'rider_id']) as $row) {
                $title = trim((string) ($row->detail ?? ''));
                if ($title === '') {
                    $title = trim((string) ($row->trans_code ?? $row->reference_number ?? ''));
                }
                if ($title === '') {
                    $title = __('Registration document');
                }
                $items->push([
                    'id' => 'br-' . $row->id,
                    'module_label' => (string) ($config['label'] ?? 'Bike registration'),
                    'title' => $title,
                    'expiry_date' => Carbon::parse($row->expiry_date)->format('d M Y'),
                    'days_left' => (int) now()->startOfDay()->diffInDays(Carbon::parse($row->expiry_date)->startOfDay(), false),
                    'url' => self::recordUrl($config, (int) $row->id, (string) (request()->route('company_slug') ?? session('company_slug') ?? '')),
                ]);
            }
        }

        return $items->sortBy([
            ['expiry_sort', 'asc'],
            ['module_label', 'asc'],
        ])->values();
    }

    /**
     * @param  object  $row
     * @param  array<string, mixed>|null  $config
     * @return array<string, mixed>
     */
    protected static function mapFileRow(object $row, ?array $config, string $type, string $filter): array
    {
        $expiry = Carbon::parse($row->expiry_date)->startOfDay();
        $daysLeft = (int) now()->startOfDay()->diffInDays($expiry, false);
        $slug = (string) (request()->route('company_slug') ?? session('company_slug') ?? '');

        return [
            'id' => 'file-' . $row->id,
            'module_label' => (string) ($config['label'] ?? ucwords(str_replace('_', ' ', $type))),
            'title' => (string) ($row->name ?: $row->file_name ?: __('Document')),
            'expiry_date' => $expiry->format('d M Y'),
            'days_left' => $daysLeft,
            'expiry_sort' => $expiry->timestamp,
            'url' => self::recordUrl($config, (int) $row->type_id, $slug, $type),
        ];
    }

    /**
     * @return list<string>
     */
    public static function allowedFileTypesForUser(?User $user): array
    {
        if (! $user) {
            return [];
        }

        $allowed = [];
        foreach (array_keys(self::typeConfig()) as $type) {
            if ((self::typeConfig()[$type]['source'] ?? 'files') !== 'files') {
                continue;
            }
            if (self::userCanAccessSource($user, $type)) {
                $allowed[] = $type;
            }
        }

        if ($user->hasAnyRole(['Administrator', 'Super Admin'])) {
            $existing = company_table('files')
                ->whereNotNull('expiry_date')
                ->distinct()
                ->pluck('type')
                ->map(static fn ($t) => (string) $t)
                ->all();

            return array_values(array_unique(array_merge($allowed, $existing)));
        }

        return $allowed;
    }

    public static function userCanAccessSource(?User $user, string $key): bool
    {
        if (! $user) {
            return false;
        }

        if ($user->hasAnyRole(['Administrator', 'Super Admin'])) {
            return true;
        }

        $config = self::typeConfig()[$key] ?? null;
        if (! $config) {
            return $user->can('documents_view');
        }

        $visibility = (string) ($config['visibility'] ?? '');
        if ($visibility !== '' && ! CompanyModuleVisibility::enabled($visibility)) {
            return false;
        }

        foreach ((array) ($config['permissions'] ?? []) as $permission) {
            if (user_can($permission, $user)) {
                return true;
            }
        }

        return user_can('documents_view', $user);
    }

    /**
     * @param  array<string, mixed>|null  $config
     */
    protected static function recordUrl(?array $config, int $typeId, string $companySlug, ?string $fileType = null): ?string
    {
        if ($typeId < 1) {
            return null;
        }

        if ($config === null) {
            return self::listUrl('expiring', $companySlug, DocumentExpiry::windowDays());
        }

        $routeName = (string) ($config['route'] ?? '');
        if ($routeName === '' || ! Route::has($routeName)) {
            return null;
        }

        $params = ['company_slug' => $companySlug];
        $routeParam = (string) ($config['route_param'] ?? '');
        if ($routeParam !== '') {
            $params[$routeParam] = $typeId;
        }

        $query = (array) ($config['route_query'] ?? []);
        if ($fileType !== null && $routeName === 'files.index') {
            $query['type'] = $fileType;
            $query['type_id'] = $typeId;
        }

        try {
            $url = route($routeName, $params);
        } catch (\Throwable) {
            return null;
        }

        if ($query !== []) {
            $url .= (str_contains($url, '?') ? '&' : '?') . http_build_query($query);
        }

        return $url;
    }

    protected static function listUrl(string $filter, string $companySlug, int $days): string
    {
        try {
            return route('files.index', [
                'company_slug' => $companySlug,
                'expiry' => $filter,
                'days' => $days,
            ]);
        } catch (\Throwable) {
            return '#';
        }
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function typeConfig(): array
    {
        $config = config('document_expiry_modules', []);

        return is_array($config) ? $config : [];
    }
}
