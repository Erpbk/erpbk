<?php

namespace App\Services\RiderActivities;

use App\Models\RiderActivities;
use App\Models\Riders;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Calculated Rider Activities top-bar insight cards.
 * Values recompute from imported rider_activities on every listing request.
 */
class RiderActivityInsightCardsService
{
    public const KEY_NOT_WORKED = 'not_worked';

    public const KEY_LOW_HOURS = 'low_hours';

    public const KEY_HIGH_REJECTIONS = 'high_rejections';

    public const KEY_LOW_ORDERS = 'low_orders';

    public const KEY_PERFECT = 'perfect';

    public const HOURS_TARGET = 10.0;

    public const ORDERS_TARGET = 10;

    /** Daily rejected-orders threshold ("Daily Limit Exceeded"). */
    public const DAILY_REJECTION_LIMIT = 1;

    public const ABSENT_DAYS_THRESHOLD = 2;

    /**
     * @return list<string>
     */
    public static function allKeys(): array
    {
        return [
            self::KEY_NOT_WORKED,
            self::KEY_LOW_HOURS,
            self::KEY_HIGH_REJECTIONS,
            self::KEY_LOW_ORDERS,
            self::KEY_PERFECT,
        ];
    }

    public static function keyFromOptionName(string $name): ?string
    {
        $name = trim($name);
        if ($name === '') {
            return null;
        }

        if (in_array($name, self::allKeys(), true)) {
            return $name;
        }

        $labels = config('top_bar_filters.modules.activities.option_labels', []);
        foreach ($labels as $key => $label) {
            if (strcasecmp(trim((string) $label), $name) === 0 && in_array($key, self::allKeys(), true)) {
                return $key;
            }
        }

        return null;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function cards(?Request $request = null): array
    {
        $request = $request ?? request();
        $cacheKey = 'rider_activity_insight_cards';
        if ($request->attributes->has($cacheKey)) {
            return $request->attributes->get($cacheKey);
        }

        $referenceDate = $this->resolveReferenceDate($request);
        $weekStart = $referenceDate->copy()->startOfWeek(Carbon::MONDAY);
        $weekEnd = $referenceDate->copy()->endOfWeek(Carbon::SUNDAY);
        $activeKey = trim((string) $request->input('insight_card', ''));

        $dayActivities = $this->activitiesForDate($referenceDate);
        $byRider = $dayActivities->groupBy('rider_id')->map(function (Collection $rows) {
            return (object) [
                'rider_id' => (int) $rows->first()->rider_id,
                'login_hr' => (float) $rows->sum('login_hr'),
                'delivered_orders' => (int) $rows->sum('delivered_orders'),
                'rejected_orders' => (int) $rows->sum('rejected_orders'),
            ];
        });

        $notWorked = $this->notWorkedRiders($referenceDate);
        $lowHours = $byRider->filter(fn ($row) => $row->login_hr > 0 && $row->login_hr < self::HOURS_TARGET)->values();
        $highRejections = $byRider->filter(fn ($row) => $row->rejected_orders >= self::DAILY_REJECTION_LIMIT)->values();
        $lowOrders = $byRider->filter(fn ($row) => $row->delivered_orders < self::ORDERS_TARGET)->values();
        $perfect = $byRider->filter(function ($row) {
            return $row->login_hr >= self::HOURS_TARGET
                && $row->delivered_orders >= self::ORDERS_TARGET
                && $row->rejected_orders === 0;
        })->values();

        $weeklyRejectPct = $this->weeklyRejectionPercentage(
            $highRejections->pluck('rider_id')->all(),
            $weekStart,
            $weekEnd
        );

        $avgHours = $lowHours->avg('login_hr') ?? 0.0;
        $avgOrders = $lowOrders->avg('delivered_orders') ?? 0.0;
        $minAbsentDays = $notWorked->min('days_absent');

        $cards = [
            $this->card(self::KEY_NOT_WORKED, [
                'title' => 'Not Worked (2+ Days)',
                'icon' => 'ti ti-circle-off',
                'color' => 'danger',
                'count' => $notWorked->count(),
                'criteria' => '2+ Days Absent',
                'meta' => $minAbsentDays !== null
                    ? 'Last Login: ' . (int) $minAbsentDays . ' Day' . ((int) $minAbsentDays === 1 ? '' : 's') . ' Ago'
                    : 'No recent login',
                'rider_ids' => $notWorked->pluck('rider_id')->map(fn ($id) => (int) $id)->values()->all(),
                'active' => $activeKey === self::KEY_NOT_WORKED,
                'action_label' => 'Show absent riders',
            ]),
            $this->card(self::KEY_LOW_HOURS, [
                'title' => 'Less Than 10 Hours',
                'icon' => 'ti ti-alarm',
                'color' => 'warning',
                'count' => $lowHours->count(),
                'criteria' => 'Hours Completed < 10',
                'meta' => 'Hours Completed: ' . $this->formatHours((float) $avgHours)
                    . ' · Missing: ' . $this->formatHours(max(0, self::HOURS_TARGET - (float) $avgHours)),
                'rider_ids' => $lowHours->pluck('rider_id')->map(fn ($id) => (int) $id)->values()->all(),
                'active' => $activeKey === self::KEY_LOW_HOURS,
                'action_label' => 'Show riders below 10 hours',
                'reference_date' => $referenceDate->toDateString(),
            ]),
            $this->card(self::KEY_HIGH_REJECTIONS, [
                'title' => 'High Rejections',
                'icon' => 'ti ti-x',
                'color' => 'danger',
                'count' => $highRejections->count(),
                'criteria' => 'Daily Limit Exceeded',
                'meta' => 'Weekly Reject: ' . number_format($weeklyRejectPct, 0) . '%',
                'rider_ids' => $highRejections->pluck('rider_id')->map(fn ($id) => (int) $id)->values()->all(),
                'active' => $activeKey === self::KEY_HIGH_REJECTIONS,
                'action_label' => 'Show high rejection riders',
                'reference_date' => $referenceDate->toDateString(),
            ]),
            $this->card(self::KEY_LOW_ORDERS, [
                'title' => 'Low Orders',
                'icon' => 'ti ti-package',
                'color' => 'info',
                'count' => $lowOrders->count(),
                'criteria' => 'Completed < Target',
                'meta' => 'Target: ' . self::ORDERS_TARGET . ' Orders · Completed: '
                    . number_format((float) $avgOrders, 0) . ' Orders',
                'rider_ids' => $lowOrders->pluck('rider_id')->map(fn ($id) => (int) $id)->values()->all(),
                'active' => $activeKey === self::KEY_LOW_ORDERS,
                'action_label' => 'Show riders below target',
                'reference_date' => $referenceDate->toDateString(),
            ]),
            $this->card(self::KEY_PERFECT, [
                'title' => 'Perfect Riders',
                'icon' => 'ti ti-star',
                'color' => 'success',
                'count' => $perfect->count(),
                'criteria' => '10+ Hours · 10+ Orders · 0 Rejections',
                'meta' => 'Top performers on ' . $referenceDate->format('d M Y'),
                'rider_ids' => $perfect->pluck('rider_id')->map(fn ($id) => (int) $id)->values()->all(),
                'active' => $activeKey === self::KEY_PERFECT,
                'action_label' => 'Show top performers',
                'reference_date' => $referenceDate->toDateString(),
            ]),
        ];

        $request->attributes->set($cacheKey, $cards);

        return $cards;
    }

    /**
     * Rider IDs matching a card key (for listing filters).
     *
     * @return list<int>
     */
    public function riderIdsForCard(string $key, ?Request $request = null): array
    {
        foreach ($this->cards($request) as $card) {
            if (($card['key'] ?? '') === $key) {
                return array_values(array_map('intval', $card['rider_ids'] ?? []));
            }
        }

        return [];
    }

    public function applyInsightFilter($query, Request $request, string $riderIdColumn = 'rider_id'): void
    {
        $key = trim((string) $request->input('insight_card', ''));
        if ($key === '') {
            return;
        }

        $this->applyInsightKeyFilter($query, $key, $request, $riderIdColumn);
    }

    public function applyInsightKeyFilter($query, string $key, Request $request, string $riderIdColumn = 'rider_id'): void
    {
        $key = trim($key);
        if ($key === '' || ! in_array($key, self::allKeys(), true)) {
            return;
        }

        $card = null;
        foreach ($this->cards($request) as $item) {
            if (($item['key'] ?? '') === $key) {
                $card = $item;
                break;
            }
        }

        $ids = array_values(array_map('intval', $card['rider_ids'] ?? []));
        if ($ids === []) {
            $query->whereRaw('1 = 0');

            return;
        }

        $query->whereIn($riderIdColumn, $ids);

        // Day-based cards should lock the listing to the calculation date.
        if (
            $key !== self::KEY_NOT_WORKED
            && ! empty($card['reference_date'])
            && ! $request->filled('from_date')
            && ! $request->filled('to_date')
            && ! $request->filled('billing_month')
        ) {
            $query->whereDate('date', $card['reference_date']);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function card(string $key, array $data): array
    {
        return array_merge([
            'key' => $key,
            'title' => '',
            'icon' => 'ti ti-info-circle',
            'color' => 'secondary',
            'count' => 0,
            'criteria' => '',
            'meta' => '',
            'rider_ids' => [],
            'active' => false,
            'action_label' => 'View riders',
        ], $data);
    }

    private function resolveReferenceDate(Request $request): Carbon
    {
        if ($request->filled('to_date')) {
            return Carbon::parse($request->to_date)->startOfDay();
        }
        if ($request->filled('from_date')) {
            return Carbon::parse($request->from_date)->startOfDay();
        }
        if ($request->filled('billing_month')) {
            try {
                $month = Carbon::parse($request->billing_month . '-01')->startOfMonth();
                $today = Carbon::today();
                if ($today->betweenIncluded($month, $month->copy()->endOfMonth())) {
                    return $today;
                }

                return $month->copy()->endOfMonth()->startOfDay();
            } catch (\Throwable $e) {
                // fall through
            }
        }

        $latest = RiderActivities::query()->max('date');
        $today = Carbon::today();
        if ($latest) {
            $latestDate = Carbon::parse($latest)->startOfDay();
            if ($latestDate->equalTo($today) || $latestDate->greaterThan($today)) {
                return $today;
            }

            return $latestDate;
        }

        return $today;
    }

    private function activitiesForDate(Carbon $date): Collection
    {
        return RiderActivities::query()
            ->whereDate('date', $date->toDateString())
            ->whereNotNull('rider_id')
            ->get(['rider_id', 'login_hr', 'delivered_orders', 'rejected_orders', 'date']);
    }

    /**
     * Active riders with no activity in the last ABSENT_DAYS_THRESHOLD days
     * (last login at least 2 days ago, or never).
     *
     * @return Collection<int, object{rider_id:int, days_absent:int, last_login:?string}>
     */
    private function notWorkedRiders(Carbon $referenceDate): Collection
    {
        $cutoff = $referenceDate->copy()->subDays(self::ABSENT_DAYS_THRESHOLD - 1)->startOfDay();

        $lastLogins = RiderActivities::query()
            ->select('rider_id', DB::raw('MAX(date) as last_login'))
            ->whereNotNull('rider_id')
            ->groupBy('rider_id')
            ->pluck('last_login', 'rider_id');

        // Current primary status = Active (exact), matching sidebar filter semantics.
        $activeExact = Riders::query();
        Riders::applyCurrentStatusFilter($activeExact, 'Active');
        $activeRiderIds = $activeExact->pluck('id');

        return $activeRiderIds
            ->map(function ($riderId) use ($lastLogins, $referenceDate, $cutoff) {
                $riderId = (int) $riderId;
                $last = $lastLogins->get($riderId);
                if ($last) {
                    $lastDate = Carbon::parse($last)->startOfDay();
                    if ($lastDate->greaterThanOrEqualTo($cutoff)) {
                        return null;
                    }
                    $daysAbsent = max(self::ABSENT_DAYS_THRESHOLD, $lastDate->diffInDays($referenceDate));
                } else {
                    $daysAbsent = max(self::ABSENT_DAYS_THRESHOLD, 99);
                    $lastDate = null;
                }

                return (object) [
                    'rider_id' => $riderId,
                    'days_absent' => (int) $daysAbsent,
                    'last_login' => $lastDate?->toDateString(),
                ];
            })
            ->filter()
            ->values();
    }

    /**
     * @param  list<int>  $riderIds
     */
    private function weeklyRejectionPercentage(array $riderIds, Carbon $weekStart, Carbon $weekEnd): float
    {
        if ($riderIds === []) {
            return 0.0;
        }

        $rows = RiderActivities::query()
            ->whereIn('rider_id', $riderIds)
            ->whereDate('date', '>=', $weekStart->toDateString())
            ->whereDate('date', '<=', $weekEnd->toDateString())
            ->get(['delivered_orders', 'rejected_orders']);

        $delivered = (float) $rows->sum('delivered_orders');
        $rejected = (float) $rows->sum('rejected_orders');
        $denom = $delivered + $rejected;
        if ($denom <= 0) {
            return 0.0;
        }

        return round(($rejected / $denom) * 100, 1);
    }

    public function formatHours(float $hours): string
    {
        $hours = max(0, $hours);
        $h = (int) floor($hours);
        $m = (int) round(($hours - $h) * 60);
        if ($m === 60) {
            $h++;
            $m = 0;
        }

        return $h . 'h ' . str_pad((string) $m, 2, '0', STR_PAD_LEFT) . 'm';
    }
}
