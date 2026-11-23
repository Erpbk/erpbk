<?php

namespace App\Services\RiderActivities;

use App\Models\Company;
use App\Models\liveactivities;
use App\Models\RiderActivities;
use App\Models\Riders;
use App\Models\WhatsAppTemplate;
use App\Services\WhatsApp\Context\WhatsAppContextBuilderRegistry;
use App\Services\WhatsApp\WhatsAppTemplateBindingService;
use App\Services\WhatsApp\WhatsAppTemplateInjector;
use App\Support\CompanyContext;
use App\Support\PhoneNumber;
use App\Support\WhatsApp\WhatsAppSendContext;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class RiderActivityWhatsAppAlertService
{
    public function __construct(
        private readonly RiderActivityInsightCardsService $insights,
        private readonly WhatsAppTemplateBindingService $bindings,
        private readonly WhatsAppContextBuilderRegistry $contexts,
        private readonly WhatsAppTemplateInjector $injector
    ) {
    }

    /**
     * Build filter payload for one alert type using the submitted parameters.
     *
     * @return array{
     *   mode: string,
     *   alert_type: string,
     *   date: string|null,
     *   types: array<string, list<array<string, mixed>>>,
     *   bindings: array<string, array<string, mixed>>,
     *   params: array<string, mixed>
     * }
     */
    public function filterPayload(Request $request, string $mode = 'activities'): array
    {
        $mode = in_array($mode, ['activities', 'summary', 'live'], true) ? $mode : 'activities';
        $typeDefs = $this->alertTypeDefs($mode);
        $validKeys = collect($typeDefs)->pluck('key')->all();
        $alertType = trim((string) $request->input('alert_type', ''));
        if ($alertType === '' || ! in_array($alertType, $validKeys, true)) {
            $alertType = (string) ($validKeys[0] ?? 'abs');
        }

        $projectId = $request->filled('project_id')
            ? (int) $request->input('project_id')
            : ($request->filled('customer_id') ? (int) $request->input('customer_id') : null);
        $supervisor = trim((string) $request->input('fleet_supervisor', ''));
        $hoursTarget = (float) ($request->input('hours_target') ?: RiderActivityInsightCardsService::HOURS_TARGET);
        $ordersTarget = (int) ($request->input('orders_target') ?: RiderActivityInsightCardsService::ORDERS_TARGET);
        $daysThreshold = max(1, (int) ($request->input('days_threshold') ?: RiderActivityInsightCardsService::ABSENT_DAYS_THRESHOLD));
        $alertDate = $this->parseAlertDate($request->input('alert_date'));

        $companyId = (int) (CompanyContext::id() ?? 0);
        $companyName = $companyId > 0
            ? (string) (Company::query()->whereKey($companyId)->value('name') ?? '')
            : '';

        $resolved = $this->resolveRidersForAlert(
            $mode,
            $alertType,
            $projectId,
            $supervisor !== '' ? $supervisor : null,
            $daysThreshold,
            $hoursTarget,
            $ordersTarget,
            $alertDate
        );

        $referenceDate = $resolved['reference_date'];
        $matched = $resolved['rows']; // list of [id, days_absent?, hours?, orders?]

        $riderIds = array_map(fn ($r) => (int) $r['id'], $matched);
        $riders = Riders::query()
            ->whereIn('id', $riderIds ?: [0])
            ->get([
                'id', 'rider_id', 'name', 'fleet_supervisor',
                'personal_contact', 'company_contact', 'image_name',
            ])
            ->keyBy('id');

        $lastWorked = $this->insights->lastWorkedMap();

        $bindings = [];
        $typesPayload = [];
        foreach ($typeDefs as $type) {
            $trigger = 'rider_activity:'.$type['key'];
            $bindingResolved = $this->bindings->resolve($trigger);
            $template = ($bindingResolved['ready'] ?? false) && ($bindingResolved['template'] instanceof WhatsAppTemplate)
                ? $bindingResolved['template']
                : null;
            $bindings[$type['key']] = [
                'trigger' => $trigger,
                'bound' => $template !== null,
                'template_id' => $template?->id,
                'template_label' => $template?->displayLabel(),
                'message' => $bindingResolved['message'] ?? null,
                'body_preview' => $template?->body_preview,
            ];
            $typesPayload[$type['key']] = [];
        }

        $trigger = 'rider_activity:'.$alertType;
        $template = ! empty($bindings[$alertType]['bound'])
            ? $this->bindings->resolve($trigger)['template'] ?? null
            : null;
        if (! $template instanceof WhatsAppTemplate) {
            $template = null;
        }

        $rows = [];
        foreach ($matched as $meta) {
            $rider = $riders->get((int) $meta['id']);
            if (! $rider) {
                continue;
            }
            $row = $this->riderRow(
                $rider,
                $lastWorked,
                $companyName,
                $referenceDate,
                isset($meta['days_absent']) ? (int) $meta['days_absent'] : $daysThreshold
            );
            if (isset($meta['hours'])) {
                $row['hours'] = (string) $meta['hours'];
            }
            if (isset($meta['orders'])) {
                $row['orders'] = (string) $meta['orders'];
            }
            if (in_array($alertType, ['hrs', 'lhrs'], true)) {
                $row['target'] = (string) $hoursTarget;
            } elseif (in_array($alertType, ['ord', 'lord'], true)) {
                $row['target'] = (string) $ordersTarget;
            }
            $rows[] = $this->withPreview($row, $trigger, $template, $companyName);
        }
        usort($rows, fn ($a, $b) => strcasecmp($a['name'], $b['name']));
        $typesPayload[$alertType] = $rows;

        return [
            'mode' => $mode,
            'alert_type' => $alertType,
            'date' => $referenceDate,
            'types' => $typesPayload,
            'bindings' => $bindings,
            'params' => [
                'project_id' => $projectId,
                'fleet_supervisor' => $supervisor !== '' ? $supervisor : null,
                'hours_target' => $hoursTarget,
                'orders_target' => $ordersTarget,
                'days_threshold' => $daysThreshold,
                'alert_date' => $alertDate?->toDateString() ?? $referenceDate,
            ],
        ];
    }

    /**
     * @return list<array{key: string, label: string, match: list<string>}>
     */
    public function alertTypeDefs(string $mode): array
    {
        return match ($mode) {
            'live' => [
                ['key' => 'labs', 'label' => 'Absent today', 'match' => ['labs']],
                ['key' => 'lhrs', 'label' => 'Low hours today', 'match' => ['low_hours', 'hrs']],
                ['key' => 'lord', 'label' => 'Low orders today', 'match' => ['low_orders', 'ord']],
            ],
            'summary' => [
                ['key' => 'off2', 'label' => 'Not working 2–3 days', 'match' => ['off2']],
                ['key' => 'off4', 'label' => 'Not working 4–5 days', 'match' => ['off4']],
                ['key' => 'off6', 'label' => 'Not working 6+ days', 'match' => ['off6']],
                ['key' => 'hrs', 'label' => 'Less than 10 hours', 'match' => ['low_hours', 'hrs']],
                ['key' => 'ord', 'label' => 'Less than 15 orders', 'match' => ['low_orders', 'ord']],
            ],
            default => [
                ['key' => 'abs', 'label' => 'Absent', 'match' => ['abs']],
                ['key' => 'off', 'label' => 'Not working', 'match' => ['off2', 'off4', 'off6']],
                ['key' => 'hrs', 'label' => 'Duty hours not completed', 'match' => ['low_hours', 'hrs']],
                ['key' => 'ord', 'label' => 'Daily target not met', 'match' => ['low_orders', 'ord']],
            ],
        };
    }

    public function buildContextForRider(array $riderRow, string $companyName = '', string $triggerKey = 'rider_activity:abs'): ?WhatsAppSendContext
    {
        return $this->contexts->build($triggerKey, [
            'rider' => $riderRow,
            'company_name' => $companyName !== '' ? $companyName : ($riderRow['company_name'] ?? null),
        ]);
    }

    /**
     * @return array{reference_date: string|null, rows: list<array<string, mixed>>}
     */
    private function resolveRidersForAlert(
        string $mode,
        string $alertType,
        ?int $projectId,
        ?string $supervisor,
        int $daysThreshold,
        float $hoursTarget,
        int $ordersTarget,
        ?Carbon $alertDate = null
    ): array {
        $activeRiders = $this->activeRidersScoped($projectId, $supervisor);

        if ($mode === 'live') {
            // Live hours/orders are always locked to today.
            $reference = in_array($alertType, ['lhrs', 'lord'], true)
                ? Carbon::today()->startOfDay()
                : $this->insights->resolveLiveReferenceDate(request());
            $byRider = $this->dayActivityByRider($reference, true);
            $refStr = $reference->toDateString();

            return match ($alertType) {
                'labs' => [
                    'reference_date' => $refStr,
                    'rows' => $activeRiders
                        ->reject(fn ($r) => (($byRider[(int) $r->id]->login_hr ?? 0) > 0))
                        ->map(fn ($r) => ['id' => (int) $r->id, 'days_absent' => 1])
                        ->values()
                        ->all(),
                ],
                'lhrs' => [
                    'reference_date' => $refStr,
                    'rows' => $this->filterLowHours($activeRiders, $byRider, $hoursTarget),
                ],
                'lord' => [
                    'reference_date' => $refStr,
                    'rows' => $this->filterLowOrders($activeRiders, $byRider, $ordersTarget),
                ],
                default => ['reference_date' => $refStr, 'rows' => []],
            };
        }

        $usesAlertDate = in_array($alertType, ['abs', 'off', 'off2', 'off4', 'off6', 'hrs', 'ord'], true);
        if ($usesAlertDate) {
            $fallback = in_array($alertType, ['off', 'off2', 'off4', 'off6'], true)
                ? Carbon::today()->startOfDay()
                : Carbon::yesterday()->startOfDay();
            $reference = $alertDate?->copy()->startOfDay() ?: $fallback;
        } else {
            $reference = $this->insights->resolveReferenceDate(request());
        }
        $refStr = $reference->toDateString();
        $byRider = $this->dayActivityByRider($reference, false);
        $lastWorked = $this->insights->lastWorkedMap();

        if (in_array($alertType, ['abs', 'labs'], true)) {
            $rows = [];
            foreach ($activeRiders as $rider) {
                $id = (int) $rider->id;
                $worked = ($byRider[$id]->login_hr ?? 0) > 0;
                if ($worked) {
                    continue;
                }
                $days = $this->daysOff($lastWorked->get($id), $reference);
                $rows[] = ['id' => $id, 'days_absent' => max(1, $days)];
            }

            return ['reference_date' => $refStr, 'rows' => $rows];
        }

        if (in_array($alertType, ['off', 'off2', 'off4', 'off6'], true)) {
            [$minDays, $maxDays] = match ($alertType) {
                'off2' => [2, 3],
                'off4' => [4, 5],
                'off6' => [6, null],
                default => [$daysThreshold, null], // off: user threshold or more
            };
            $rows = [];
            foreach ($activeRiders as $rider) {
                $id = (int) $rider->id;
                $days = $this->daysOff($lastWorked->get($id), $reference);
                if ($days < $minDays) {
                    continue;
                }
                if ($maxDays !== null && $days > $maxDays) {
                    continue;
                }
                $rows[] = ['id' => $id, 'days_absent' => $days];
            }

            return ['reference_date' => $refStr, 'rows' => $rows];
        }

        if (in_array($alertType, ['hrs', 'lhrs'], true)) {
            return [
                'reference_date' => $refStr,
                'rows' => $this->filterLowHours($activeRiders, $byRider, $hoursTarget),
            ];
        }

        if (in_array($alertType, ['ord', 'lord'], true)) {
            return [
                'reference_date' => $refStr,
                'rows' => $this->filterLowOrders($activeRiders, $byRider, $ordersTarget),
            ];
        }

        return ['reference_date' => $refStr, 'rows' => []];
    }

    /**
     * @param  Collection<int, Riders>  $activeRiders
     * @param  array<int, object>  $byRider
     * @return list<array<string, mixed>>
     */
    private function filterLowHours(Collection $activeRiders, array $byRider, float $hoursTarget): array
    {
        $rows = [];
        foreach ($activeRiders as $rider) {
            $id = (int) $rider->id;
            $hours = (float) ($byRider[$id]->login_hr ?? 0);
            $orders = (int) ($byRider[$id]->delivered_orders ?? 0);
            if ($hours <= 0 || $hours >= $hoursTarget) {
                continue;
            }
            $rows[] = ['id' => $id, 'hours' => $hours, 'orders' => $orders];
        }

        return $rows;
    }

    /**
     * @param  Collection<int, Riders>  $activeRiders
     * @param  array<int, object>  $byRider
     * @return list<array<string, mixed>>
     */
    private function filterLowOrders(Collection $activeRiders, array $byRider, int $ordersTarget): array
    {
        $rows = [];
        foreach ($activeRiders as $rider) {
            $id = (int) $rider->id;
            $hours = (float) ($byRider[$id]->login_hr ?? 0);
            $orders = (int) ($byRider[$id]->delivered_orders ?? 0);
            if ($hours <= 0 || $orders >= $ordersTarget) {
                continue;
            }
            $rows[] = ['id' => $id, 'orders' => $orders, 'hours' => $hours];
        }

        return $rows;
    }

    private function parseAlertDate(mixed $value): ?Carbon
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }
        try {
            $date = Carbon::parse(trim($value))->startOfDay();
        } catch (\Throwable $e) {
            return null;
        }
        if ($date->greaterThan(Carbon::today()->startOfDay())) {
            return Carbon::yesterday()->startOfDay();
        }

        return $date;
    }

    private function daysOff(mixed $lastWorkedRaw, Carbon $referenceDate): int
    {
        if (! $lastWorkedRaw) {
            return 99;
        }
        try {
            $lastDate = Carbon::parse($lastWorkedRaw)->startOfDay();
        } catch (\Throwable $e) {
            return 99;
        }
        if ($lastDate->equalTo($referenceDate)) {
            return 0;
        }

        return max(1, (int) $lastDate->diffInDays($referenceDate));
    }

    /**
     * @return array<int, object{rider_id:int,login_hr:float,delivered_orders:int}>
     */
    private function dayActivityByRider(Carbon $date, bool $live): array
    {
        $query = $live
            ? liveactivities::query()->whereDate('date', $date->toDateString())
            : RiderActivities::query()->whereDate('date', $date->toDateString());

        $rows = $query->whereNotNull('rider_id')
            ->get(['rider_id', 'login_hr', 'delivered_orders']);

        $out = [];
        foreach ($rows->groupBy('rider_id') as $riderId => $group) {
            $out[(int) $riderId] = (object) [
                'rider_id' => (int) $riderId,
                'login_hr' => (float) $group->sum('login_hr'),
                'delivered_orders' => (int) $group->sum('delivered_orders'),
            ];
        }

        return $out;
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
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function withPreview(
        array $row,
        string $trigger,
        ?WhatsAppTemplate $template,
        string $companyName
    ): array {
        $row['preview_text'] = null;
        $row['preview_ok'] = false;
        $row['missing_injects'] = [];
        $row['missing_inject_labels'] = [];
        $row['preview_error'] = null;

        if (! $template) {
            return $row;
        }

        $context = $this->buildContextForRider($row, $companyName, $trigger);
        if (! $context) {
            $row['preview_error'] = 'Could not build message context for this rider.';

            return $row;
        }

        // Rider alerts fill blank injects with "-" so preview/send still work.
        $payload = $this->injector->previewPayload($template, $context, '-');
        $row['preview_ok'] = (bool) ($payload['ok'] ?? false);
        $row['preview_text'] = $payload['text'] ?? null;
        $row['missing_injects'] = $payload['missing_injects'] ?? [];
        $row['missing_inject_labels'] = $payload['missing_inject_labels'] ?? [];
        $row['preview_error'] = $payload['error'] ?? null;

        return $row;
    }

    /**
     * @param  Collection<int, object>|null  $lastWorked
     * @return array<string, mixed>
     */
    private function riderRow($rider, $lastWorked, string $companyName, ?string $referenceDate, int $daysAbsent): array
    {
        $phoneRaw = $rider->company_contact ?: $rider->personal_contact;
        $phone = PhoneNumber::toWhatsAppDigits(is_string($phoneRaw) ? $phoneRaw : null);
        $last = $lastWorked?->get((int) $rider->id);
        $lastDate = $last ? date('d M Y', strtotime((string) $last)) : null;
        if ($last && $referenceDate) {
            try {
                $daysAbsent = max(1, (int) Carbon::parse($last)->startOfDay()
                    ->diffInDays(Carbon::parse($referenceDate)->startOfDay()));
            } catch (\Throwable $e) {
                // keep provided daysAbsent
            }
        }

        $initials = collect(preg_split('/\s+/', trim((string) $rider->name)))
            ->filter()
            ->take(2)
            ->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))
            ->implode('');

        $alertDateDisplay = null;
        if ($referenceDate) {
            try {
                $alertDateDisplay = Carbon::parse($referenceDate)->format('d M Y');
            } catch (\Throwable $e) {
                $alertDateDisplay = $referenceDate;
            }
        }

        return [
            'id' => (int) $rider->id,
            'code' => (string) ($rider->rider_id ?? $rider->id),
            'name' => (string) $rider->name,
            'sup' => (string) ($rider->fleet_supervisor ?? ''),
            'phone' => PhoneNumber::isValidWhatsAppDigits($phone) ? $phone : '',
            'ini' => $initials !== '' ? $initials : 'R',
            'days_absent' => $daysAbsent,
            'last_date' => $lastDate ?: '—',
            'last_worked_date' => $lastDate,
            'alert_date' => $alertDateDisplay,
            'company_name' => $companyName,
        ];
    }
}
