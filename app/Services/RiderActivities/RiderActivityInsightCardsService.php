<?php

namespace App\Services\RiderActivities;

use App\Models\Customers;
use App\Models\RiderActivities;
use App\Models\Riders;
use App\Models\liveactivities;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Calculated Rider Activities insight cards + project ticker stats.
 * Values recompute from imported rider_activities on every listing request.
 */
class RiderActivityInsightCardsService
{
    public const KEY_TOTAL = 'total';

    public const KEY_ABSENT = 'abs';

    public const KEY_OFF_2 = 'off2';

    public const KEY_OFF_4 = 'off4';

    public const KEY_OFF_6 = 'off6';

    public const KEY_NOT_WORKED = 'not_worked';

    public const KEY_LOW_HOURS = 'low_hours';

    public const KEY_HIGH_REJECTIONS = 'high_rejections';

    public const KEY_LOW_ORDERS = 'low_orders';

    public const KEY_PERFECT = 'perfect';

    public const KEY_PRESENT = 'pres';

    public const KEY_LIVE_ABSENT = 'labs';

    public const HOURS_TARGET = 10.0;

    public const ORDERS_TARGET = 15;

    /** Daily rejected-orders threshold ("Daily Limit Exceeded"). */
    public const DAILY_REJECTION_LIMIT = 1;

    public const ABSENT_DAYS_THRESHOLD = 2;

    /** @var list<array{0:string,1:string}> */
    private const PROJECT_PALETTE = [
        ['#3b3fd8', '#eef0ff'],
        ['#0284c7', '#e0f2fe'],
        ['#15803d', '#dcfce7'],
        ['#c2410c', '#ffedd5'],
        ['#be185d', '#fce7f3'],
        ['#7c3aed', '#f5f3ff'],
        ['#0f766e', '#ccfbf1'],
        ['#b45309', '#fef3c7'],
    ];

    /**
     * @return list<string>
     */
    public static function allKeys(): array
    {
        return [
            self::KEY_TOTAL,
            self::KEY_ABSENT,
            self::KEY_OFF_2,
            self::KEY_OFF_4,
            self::KEY_OFF_6,
            self::KEY_NOT_WORKED,
            self::KEY_LOW_HOURS,
            self::KEY_HIGH_REJECTIONS,
            self::KEY_LOW_ORDERS,
            self::KEY_PERFECT,
            self::KEY_PRESENT,
            self::KEY_LIVE_ABSENT,
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

        $aliases = [
            'abs' => self::KEY_ABSENT,
            'absent' => self::KEY_ABSENT,
            'absent yesterday' => self::KEY_ABSENT,
            'off2' => self::KEY_OFF_2,
            'off 2-3 days' => self::KEY_OFF_2,
            'not working · 2–3 days' => self::KEY_OFF_2,
            'not working 2-3 days' => self::KEY_OFF_2,
            'off4' => self::KEY_OFF_4,
            'off 4-5 days' => self::KEY_OFF_4,
            'not working · 4–5 days' => self::KEY_OFF_4,
            'off6' => self::KEY_OFF_6,
            'off 6+ days' => self::KEY_OFF_6,
            'not working · 6+ days' => self::KEY_OFF_6,
            'hrs' => self::KEY_LOW_HOURS,
            'under 10 hours' => self::KEY_LOW_HOURS,
            'less than 10 hours' => self::KEY_LOW_HOURS,
            'ord' => self::KEY_LOW_ORDERS,
            'under 15 orders' => self::KEY_LOW_ORDERS,
            'less than 15 orders' => self::KEY_LOW_ORDERS,
            'pres' => self::KEY_PRESENT,
            'present today' => self::KEY_PRESENT,
            'labs' => self::KEY_LIVE_ABSENT,
            'absent today' => self::KEY_LIVE_ABSENT,
        ];

        $lower = strtolower($name);
        if (isset($aliases[$lower])) {
            return $aliases[$lower];
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
        $projectId = $request->filled('customer_id') ? (int) $request->input('customer_id') : null;
        $supervisor = trim((string) $request->input('fleet_supervisor', ''));

        $dayActivities = $this->activitiesForDate($referenceDate);
        $byRider = $dayActivities->groupBy('rider_id')->map(function (Collection $rows) {
            return (object) [
                'rider_id' => (int) $rows->first()->rider_id,
                'login_hr' => (float) $rows->sum('login_hr'),
                'delivered_orders' => (int) $rows->sum('delivered_orders'),
                'rejected_orders' => (int) $rows->sum('rejected_orders'),
            ];
        });

        $workedToday = $byRider->filter(fn ($row) => $row->login_hr > 0)->keyBy('rider_id');
        $activeRiders = $this->activeRidersScoped($projectId, $supervisor !== '' ? $supervisor : null);
        $lastWorked = $this->lastWorkedMap();

        $absent = collect();
        $off2 = collect();
        $off4 = collect();
        $off6 = collect();

        foreach ($activeRiders as $rider) {
            $riderId = (int) $rider->id;
            $last = $lastWorked->get($riderId);
            $lastDate = $last ? Carbon::parse($last)->startOfDay() : null;
            $daysOff = $lastDate
                ? max(0, $lastDate->diffInDays($referenceDate) - ($lastDate->equalTo($referenceDate) ? 0 : 0))
                : 99;

            // Days since last work, not counting the reference day itself when they worked.
            if ($lastDate && $lastDate->equalTo($referenceDate)) {
                $daysOff = 0;
            } elseif ($lastDate) {
                $daysOff = max(1, $lastDate->diffInDays($referenceDate));
            } else {
                $daysOff = 99;
            }

            if (! $workedToday->has($riderId)) {
                $absent->push((object) [
                    'rider_id' => $riderId,
                    'days_absent' => max(1, $daysOff),
                    'last_login' => $lastDate?->toDateString(),
                ]);
            }

            if ($daysOff >= 6) {
                $off6->push((object) ['rider_id' => $riderId, 'days_absent' => $daysOff, 'last_login' => $lastDate?->toDateString()]);
            } elseif ($daysOff >= 4) {
                $off4->push((object) ['rider_id' => $riderId, 'days_absent' => $daysOff, 'last_login' => $lastDate?->toDateString()]);
            } elseif ($daysOff >= 2) {
                $off2->push((object) ['rider_id' => $riderId, 'days_absent' => $daysOff, 'last_login' => $lastDate?->toDateString()]);
            }
        }

        $scopedDay = $byRider->filter(function ($row) use ($activeRiders) {
            return $activeRiders->contains('id', $row->rider_id);
        });

        $lowHours = $scopedDay->filter(fn ($row) => $row->login_hr > 0 && $row->login_hr < self::HOURS_TARGET)->values();
        $highRejections = $scopedDay->filter(fn ($row) => $row->rejected_orders >= self::DAILY_REJECTION_LIMIT)->values();
        $lowOrders = $scopedDay->filter(fn ($row) => $row->login_hr > 0 && $row->delivered_orders < self::ORDERS_TARGET)->values();
        $perfect = $scopedDay->filter(function ($row) {
            return $row->login_hr >= self::HOURS_TARGET
                && $row->delivered_orders >= self::ORDERS_TARGET
                && $row->rejected_orders === 0;
        })->values();

        $notWorked = $off2->concat($off4)->concat($off6)->unique('rider_id')->values();
        $weeklyRejectPct = $this->weeklyRejectionPercentage(
            $highRejections->pluck('rider_id')->all(),
            $weekStart,
            $weekEnd
        );

        $avgHours = $lowHours->avg('login_hr') ?? 0.0;
        $avgOrders = $lowOrders->avg('delivered_orders') ?? 0.0;
        $scopeLabel = $this->scopeLabel($projectId);
        $presentCount = max(0, $activeRiders->count() - $absent->count());

        $cards = [
            $this->card(self::KEY_TOTAL, [
                'title' => 'Total active riders',
                'icon' => 'ti ti-users',
                'color' => 'blue',
                'count' => $activeRiders->count(),
                'criteria' => 'Active',
                'meta' => $presentCount . ' present · ' . $absent->count() . ' absent',
                'rider_ids' => $activeRiders->pluck('id')->map(fn ($id) => (int) $id)->values()->all(),
                'active' => $activeKey === '' || $activeKey === self::KEY_TOTAL,
                'action_label' => 'Show all active riders',
                'demo_variant' => 'blue',
            ]),
            $this->card(self::KEY_ABSENT, [
                'title' => 'Absent yesterday',
                'icon' => 'ti ti-user-off',
                'color' => 'rose',
                'count' => $absent->count(),
                'criteria' => 'No hours on ' . $referenceDate->format('d M'),
                'meta' => $referenceDate->format('d M') . ' · Project: ' . $scopeLabel,
                'rider_ids' => $absent->pluck('rider_id')->map(fn ($id) => (int) $id)->values()->all(),
                'active' => $activeKey === self::KEY_ABSENT,
                'action_label' => 'Show absent riders',
                'reference_date' => $referenceDate->toDateString(),
                'demo_variant' => 'rose',
            ]),
            $this->card(self::KEY_OFF_2, [
                'title' => 'Off 2-3 days',
                'icon' => 'ti ti-circle-minus',
                'color' => 'amber',
                'count' => $off2->count(),
                'criteria' => '2–3 Days Absent',
                'meta' => 'Project: ' . $scopeLabel,
                'rider_ids' => $off2->pluck('rider_id')->map(fn ($id) => (int) $id)->values()->all(),
                'active' => $activeKey === self::KEY_OFF_2,
                'action_label' => 'Show riders off 2–3 days',
                'demo_variant' => 'amber',
            ]),
            $this->card(self::KEY_OFF_4, [
                'title' => 'Off 4-5 days',
                'icon' => 'ti ti-circle-minus',
                'color' => 'orange',
                'count' => $off4->count(),
                'criteria' => '4–5 Days Absent',
                'meta' => 'Project: ' . $scopeLabel,
                'rider_ids' => $off4->pluck('rider_id')->map(fn ($id) => (int) $id)->values()->all(),
                'active' => $activeKey === self::KEY_OFF_4,
                'action_label' => 'Show riders off 4–5 days',
                'demo_variant' => 'orange',
            ]),
            $this->card(self::KEY_OFF_6, [
                'title' => 'Off 6+ days',
                'icon' => 'ti ti-circle-off',
                'color' => 'red',
                'count' => $off6->count(),
                'criteria' => '6+ Days Absent',
                'meta' => 'Project: ' . $scopeLabel,
                'rider_ids' => $off6->pluck('rider_id')->map(fn ($id) => (int) $id)->values()->all(),
                'active' => $activeKey === self::KEY_OFF_6,
                'action_label' => 'Show riders off 6+ days',
                'demo_variant' => 'red',
            ]),
            $this->card(self::KEY_NOT_WORKED, [
                'title' => 'Not Worked (2+ Days)',
                'icon' => 'ti ti-circle-off',
                'color' => 'danger',
                'count' => $notWorked->count(),
                'criteria' => '2+ Days Absent',
                'meta' => 'Project: ' . $scopeLabel,
                'rider_ids' => $notWorked->pluck('rider_id')->map(fn ($id) => (int) $id)->values()->all(),
                'active' => $activeKey === self::KEY_NOT_WORKED,
                'action_label' => 'Show absent riders',
                'hidden_in_demo' => true,
            ]),
            $this->card(self::KEY_LOW_HOURS, [
                'title' => 'Under 10 hours',
                'icon' => 'ti ti-alarm',
                'color' => 'violet',
                'count' => $lowHours->count(),
                'criteria' => 'Hours Completed < 10',
                'meta' => 'Project: ' . $scopeLabel,
                'rider_ids' => $lowHours->pluck('rider_id')->map(fn ($id) => (int) $id)->values()->all(),
                'active' => $activeKey === self::KEY_LOW_HOURS,
                'action_label' => 'Show riders below 10 hours',
                'reference_date' => $referenceDate->toDateString(),
                'demo_variant' => 'violet',
            ]),
            $this->card(self::KEY_LOW_ORDERS, [
                'title' => 'Under 15 orders',
                'icon' => 'ti ti-package',
                'color' => 'violet',
                'count' => $lowOrders->count(),
                'criteria' => 'Completed < Target',
                'meta' => 'Project: ' . $scopeLabel,
                'rider_ids' => $lowOrders->pluck('rider_id')->map(fn ($id) => (int) $id)->values()->all(),
                'active' => $activeKey === self::KEY_LOW_ORDERS,
                'action_label' => 'Show riders below target',
                'reference_date' => $referenceDate->toDateString(),
                'demo_variant' => 'violet',
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
                'hidden_in_demo' => true,
            ]),
            $this->card(self::KEY_PERFECT, [
                'title' => 'Perfect Riders',
                'icon' => 'ti ti-star',
                'color' => 'success',
                'count' => $perfect->count(),
                'criteria' => '10+ Hours · 15+ Orders · 0 Rejections',
                'meta' => 'Top performers on ' . $referenceDate->format('d M Y'),
                'rider_ids' => $perfect->pluck('rider_id')->map(fn ($id) => (int) $id)->values()->all(),
                'active' => $activeKey === self::KEY_PERFECT,
                'action_label' => 'Show top performers',
                'reference_date' => $referenceDate->toDateString(),
                'hidden_in_demo' => true,
            ]),
        ];

        $request->attributes->set($cacheKey, $cards);

        return $cards;
    }

    /**
     * Live Activities insight cards (present / absent today).
     *
     * @return list<array<string, mixed>>
     */
    public function liveCards(?Request $request = null): array
    {
        $request = $request ?? request();
        $cacheKey = 'rider_live_insight_cards';
        if ($request->attributes->has($cacheKey)) {
            return $request->attributes->get($cacheKey);
        }

        $projectId = $request->filled('customer_id') ? (int) $request->input('customer_id') : null;
        $supervisor = trim((string) $request->input('fleet_supervisor', ''));
        $activeKey = trim((string) $request->input('insight_card', ''));
        $scopeLabel = $this->scopeLabel($projectId);
        $referenceDate = $this->resolveLiveReferenceDate($request);

        $activeRiders = $this->activeRidersScoped($projectId, $supervisor !== '' ? $supervisor : null);
        $liveRows = liveactivities::query()
            ->whereDate('date', $referenceDate->toDateString())
            ->whereIn('rider_id', $activeRiders->pluck('id'))
            ->get(['rider_id', 'login_hr', 'delivered_orders', 'rejected_orders']);

        $byRider = $liveRows->groupBy('rider_id')->map(function (Collection $rows) {
            return (object) [
                'rider_id' => (int) $rows->first()->rider_id,
                'login_hr' => (float) $rows->sum('login_hr'),
                'delivered_orders' => (int) $rows->sum('delivered_orders'),
                'rejected_orders' => (int) $rows->sum('rejected_orders'),
            ];
        });

        $present = $byRider->filter(fn ($row) => $row->login_hr > 0)->values();
        $presentIds = $present->pluck('rider_id')->map(fn ($id) => (int) $id)->all();
        $inLive = $byRider->keys()->map(fn ($id) => (int) $id)->all();
        $notInLive = $activeRiders->filter(fn ($r) => ! in_array((int) $r->id, $inLive, true));
        // Absent = active riders with no hours today (zero-hour live rows + not in live data).
        $absentIds = $activeRiders
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->reject(fn ($id) => in_array($id, $presentIds, true))
            ->values()
            ->all();

        $cards = [
            $this->card(self::KEY_TOTAL, [
                'title' => 'Total active riders',
                'icon' => 'ti ti-users',
                'color' => 'blue',
                'count' => $activeRiders->count(),
                'criteria' => 'Active',
                'meta' => $notInLive->count() . ' not in live data · ' . $scopeLabel,
                'rider_ids' => $activeRiders->pluck('id')->map(fn ($id) => (int) $id)->values()->all(),
                'active' => $activeKey === '' || $activeKey === self::KEY_TOTAL,
                'action_label' => 'Show all active riders',
                'demo_variant' => 'blue',
            ]),
            $this->card(self::KEY_PRESENT, [
                'title' => 'Present today',
                'icon' => 'ti ti-user-check',
                'color' => 'green',
                'count' => $present->count(),
                'criteria' => 'Hours > 0',
                'meta' => 'Project: ' . $scopeLabel,
                'rider_ids' => $presentIds,
                'active' => $activeKey === self::KEY_PRESENT,
                'action_label' => 'Show present riders',
                'reference_date' => $referenceDate->toDateString(),
                'demo_variant' => 'green',
            ]),
            $this->card(self::KEY_LIVE_ABSENT, [
                'title' => 'Absent today',
                'icon' => 'ti ti-user-off',
                'color' => 'rose',
                'count' => count($absentIds),
                'criteria' => 'Hours = 0',
                'meta' => 'Project: ' . $scopeLabel,
                'rider_ids' => $absentIds,
                'active' => $activeKey === self::KEY_LIVE_ABSENT,
                'action_label' => 'Show absent riders',
                'reference_date' => $referenceDate->toDateString(),
                'demo_variant' => 'rose',
            ]),
        ];

        $request->attributes->set($cacheKey, $cards);

        return $cards;
    }

    /**
     * Project ticker cards for activities / live / summary.
     *
     * @return list<array<string, mixed>>
     */
    public function projectTicker(?Request $request = null, string $mode = 'activities'): array
    {
        $request = $request ?? request();
        $projectId = $request->filled('customer_id') ? (int) $request->input('customer_id') : null;
        $referenceDate = $mode === 'live'
            ? $this->resolveLiveReferenceDate($request)
            : $this->resolveReferenceDate($request);

        $activeRiders = $this->activeRidersScoped(null, null);
        $customers = Customers::query()
            ->whereIn('id', $activeRiders->pluck('customer_id')->filter()->unique())
            ->orderBy('name')
            ->get(['id', 'name'])
            ->keyBy('id');

        $presentIds = [];
        if ($mode === 'live') {
            $presentIds = liveactivities::query()
                ->whereDate('date', $referenceDate->toDateString())
                ->where('login_hr', '>', 0)
                ->pluck('rider_id')
                ->map(fn ($id) => (int) $id)
                ->all();
        } else {
            $presentIds = RiderActivities::query()
                ->whereDate('date', $referenceDate->toDateString())
                ->where('login_hr', '>', 0)
                ->pluck('rider_id')
                ->map(fn ($id) => (int) $id)
                ->all();
        }
        $presentSet = array_fill_keys($presentIds, true);

        $ordersByProject = [];
        $hoursByProject = [];
        if ($mode === 'live') {
            $agg = liveactivities::query()
                ->whereDate('date', $referenceDate->toDateString())
                ->select('rider_id', DB::raw('SUM(delivered_orders) as orders'), DB::raw('SUM(login_hr) as hours'))
                ->groupBy('rider_id')
                ->get();
        } else {
            $from = $request->filled('from_date')
                ? Carbon::parse($request->from_date)->toDateString()
                : $referenceDate->copy()->subDays(7)->toDateString();
            $to = $request->filled('to_date')
                ? Carbon::parse($request->to_date)->toDateString()
                : $referenceDate->toDateString();
            $agg = RiderActivities::query()
                ->whereDate('date', '>=', $from)
                ->whereDate('date', '<=', $to)
                ->select('rider_id', DB::raw('SUM(delivered_orders) as orders'), DB::raw('SUM(login_hr) as hours'))
                ->groupBy('rider_id')
                ->get();
        }
        $aggByRider = $agg->keyBy(fn ($row) => (int) $row->rider_id);

        $buckets = [];
        foreach ($activeRiders as $rider) {
            $cid = (int) ($rider->customer_id ?? 0);
            if ($cid <= 0 || ! $customers->has($cid)) {
                continue;
            }
            if (! isset($buckets[$cid])) {
                $buckets[$cid] = [
                    'id' => $cid,
                    'name' => (string) $customers[$cid]->name,
                    'active' => 0,
                    'present' => 0,
                    'orders' => 0,
                    'hours' => 0.0,
                ];
            }
            $buckets[$cid]['active']++;
            if (isset($presentSet[(int) $rider->id])) {
                $buckets[$cid]['present']++;
            }
            $row = $aggByRider->get((int) $rider->id);
            if ($row) {
                $buckets[$cid]['orders'] += (int) $row->orders;
                $buckets[$cid]['hours'] += (float) $row->hours;
            }
        }

        usort($buckets, fn ($a, $b) => $b['active'] <=> $a['active']);

        $out = [];
        foreach (array_values($buckets) as $i => $b) {
            $palette = self::PROJECT_PALETTE[$i % count(self::PROJECT_PALETTE)];
            $out[] = [
                'id' => $b['id'],
                'name' => $b['name'],
                'active' => $b['active'],
                'present' => $b['present'],
                'absent' => max(0, $b['active'] - $b['present']),
                'orders' => $b['orders'],
                'hours' => (int) round($b['hours']),
                'color' => $palette[0],
                'bg' => $palette[1],
                'selected' => $projectId !== null && $projectId === (int) $b['id'],
            ];
        }

        return $out;
    }

    /**
     * Rider IDs matching a card key (for listing filters).
     *
     * @return list<int>
     */
    public function riderIdsForCard(string $key, ?Request $request = null): array
    {
        $cards = in_array($key, [self::KEY_PRESENT, self::KEY_LIVE_ABSENT], true)
            ? $this->liveCards($request)
            : $this->cards($request);

        foreach ($cards as $card) {
            if (($card['key'] ?? '') === $key) {
                return array_values(array_map('intval', $card['rider_ids'] ?? []));
            }
        }

        return [];
    }

    public function applyInsightFilter($query, Request $request, string $riderIdColumn = 'rider_id'): void
    {
        $key = trim((string) $request->input('insight_card', ''));
        if ($key === '' || $key === self::KEY_TOTAL) {
            return;
        }

        $this->applyInsightKeyFilter($query, $key, $request, $riderIdColumn);
    }

    public function applyInsightKeyFilter($query, string $key, Request $request, string $riderIdColumn = 'rider_id'): void
    {
        $key = trim($key);
        if ($key === '' || $key === self::KEY_TOTAL || ! in_array($key, self::allKeys(), true)) {
            return;
        }

        $ids = $this->riderIdsForCard($key, $request);
        if ($ids === []) {
            $query->whereRaw('1 = 0');

            return;
        }

        $query->whereIn($riderIdColumn, $ids);

        $card = null;
        foreach ((in_array($key, [self::KEY_PRESENT, self::KEY_LIVE_ABSENT], true) ? $this->liveCards($request) : $this->cards($request)) as $item) {
            if (($item['key'] ?? '') === $key) {
                $card = $item;
                break;
            }
        }

        // Day-based cards should lock the listing to the calculation date.
        if (
            ! in_array($key, [self::KEY_OFF_2, self::KEY_OFF_4, self::KEY_OFF_6, self::KEY_NOT_WORKED, self::KEY_TOTAL], true)
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
            'hidden_in_demo' => false,
        ], $data);
    }

    public function resolveReferenceDate(Request $request): Carbon
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
                    return $today->copy()->subDay()->startOfDay();
                }

                return $month->copy()->endOfMonth()->startOfDay();
            } catch (\Throwable $e) {
                // fall through
            }
        }

        // Default insight basis: yesterday (or latest activity day if older).
        $yesterday = Carbon::yesterday()->startOfDay();
        $latest = RiderActivities::query()->max('date');
        if ($latest) {
            $latestDate = Carbon::parse($latest)->startOfDay();
            if ($latestDate->lessThan($yesterday)) {
                return $latestDate;
            }
        }

        return $yesterday;
    }

    private function resolveLiveReferenceDate(Request $request): Carbon
    {
        if ($request->filled('to_date')) {
            return Carbon::parse($request->to_date)->startOfDay();
        }
        if ($request->filled('from_date')) {
            return Carbon::parse($request->from_date)->startOfDay();
        }

        $latest = liveactivities::query()->max('date');
        if ($latest) {
            return Carbon::parse($latest)->startOfDay();
        }

        return Carbon::today();
    }

    private function activitiesForDate(Carbon $date): Collection
    {
        return RiderActivities::query()
            ->whereDate('date', $date->toDateString())
            ->whereNotNull('rider_id')
            ->get(['rider_id', 'login_hr', 'delivered_orders', 'rejected_orders', 'date']);
    }

    /**
     * @return Collection<int, Riders>
     */
    private function activeRidersScoped(?int $projectId, ?string $supervisor): Collection
    {
        $query = Riders::query();
        Riders::applyCurrentStatusFilter($query, 'Active');
        if ($projectId) {
            $query->where('customer_id', $projectId);
        }
        if ($supervisor) {
            $query->where('fleet_supervisor', $supervisor);
        }

        return $query->get(['id', 'customer_id', 'fleet_supervisor', 'name', 'rider_id']);
    }

    /**
     * @return Collection<int, string>
     */
    private function lastWorkedMap(): Collection
    {
        return RiderActivities::query()
            ->select('rider_id', DB::raw('MAX(date) as last_login'))
            ->whereNotNull('rider_id')
            ->where('login_hr', '>', 0)
            ->groupBy('rider_id')
            ->pluck('last_login', 'rider_id');
    }

    private function scopeLabel(?int $projectId): string
    {
        if (! $projectId) {
            return 'All projects';
        }

        return (string) (Customers::query()->where('id', $projectId)->value('name') ?: 'All projects');
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

    /**
     * Count of riders matching alertable insight buckets (for Send Alerts badge).
     */
    public function alertsCount(?Request $request = null, string $mode = 'activities'): int
    {
        $cards = $mode === 'live' ? $this->liveCards($request) : $this->cards($request);
        $keys = $mode === 'live'
            ? [self::KEY_TOTAL]
            : ($mode === 'summary'
                ? [self::KEY_OFF_2, self::KEY_OFF_4, self::KEY_OFF_6, self::KEY_LOW_HOURS, self::KEY_LOW_ORDERS]
                : [self::KEY_ABSENT, self::KEY_OFF_2, self::KEY_OFF_4, self::KEY_OFF_6, self::KEY_LOW_HOURS, self::KEY_LOW_ORDERS]);

        $ids = [];
        foreach ($cards as $card) {
            if (! in_array($card['key'] ?? '', $keys, true)) {
                continue;
            }
            foreach ($card['rider_ids'] ?? [] as $id) {
                $ids[(int) $id] = true;
            }
        }

        return count($ids);
    }
}
