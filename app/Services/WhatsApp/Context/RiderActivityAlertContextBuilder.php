<?php

namespace App\Services\WhatsApp\Context;

use App\Models\Company;
use App\Models\Riders;
use App\Support\PhoneNumber;
use App\Support\WhatsApp\WhatsAppSendContext;
use Carbon\Carbon;

/**
 * Context for rider_activity:* triggers (absent / off / hours / orders / live).
 *
 * Maps filter + rider snapshot fields used by activities WhatsApp alerts:
 * alert_date, days_threshold → days_absent, hours/orders metrics, last worked date.
 */
class RiderActivityAlertContextBuilder implements WhatsAppContextBuilder
{
    public function provides(): array
    {
        return [
            'rider_name',
            'rider_code',
            'rider_phone',
            'days_absent',
            'last_worked_date',
            'fleet_supervisor',
            'company_name',
            'project_name',
            'hours',
            'orders',
            'target',
            'alert_date',
        ];
    }

    public function build(array $payload): WhatsAppSendContext
    {
        $snapshot = is_array($payload['rider'] ?? null) ? $payload['rider'] : $payload;
        $rider = $payload['rider_model'] ?? null;
        if (! $rider instanceof Riders && isset($payload['rider_id'])) {
            $rider = Riders::query()->find((int) $payload['rider_id']);
        }

        $companyName = $payload['company_name']
            ?? $snapshot['company_name']
            ?? null;
        if (($companyName === null || $companyName === '') && ! empty($payload['company_id'])) {
            $companyName = Company::query()->whereKey((int) $payload['company_id'])->value('name');
        }

        $phoneRaw = $payload['rider_phone']
            ?? $snapshot['phone']
            ?? $snapshot['rider_phone']
            ?? null;
        if ($phoneRaw === null && $rider) {
            $phoneRaw = $rider->company_contact ?: $rider->personal_contact;
        }
        $phone = PhoneNumber::toWhatsAppDigits(is_string($phoneRaw) ? $phoneRaw : null);
        if (! PhoneNumber::isValidWhatsAppDigits($phone)) {
            $phone = null;
        }

        $lastDate = $this->normalizeDisplayDate(
            $snapshot['last_worked_date']
                ?? $snapshot['last_date']
                ?? $payload['last_worked_date']
                ?? null
        );

        $days = $snapshot['days_absent']
            ?? $payload['days_absent']
            ?? $payload['days_threshold']
            ?? null;

        $alertDate = $this->normalizeDisplayDate(
            $snapshot['alert_date']
                ?? $payload['alert_date']
                ?? $snapshot['reference_date']
                ?? $payload['reference_date']
                ?? null
        );

        $hours = $snapshot['hours'] ?? $payload['hours'] ?? null;
        $orders = $snapshot['orders'] ?? $payload['orders'] ?? null;
        $target = $snapshot['target']
            ?? $payload['target']
            ?? $payload['hours_target']
            ?? $payload['orders_target']
            ?? null;

        return WhatsAppSendContext::fromArray([
            'rider_name' => $snapshot['name'] ?? $snapshot['rider_name'] ?? $rider?->name,
            'rider_code' => $snapshot['code'] ?? $snapshot['rider_code'] ?? ($rider?->rider_id ?? null),
            'rider_phone' => $phone,
            'days_absent' => $days !== null && $days !== '' ? (string) $days : null,
            'last_worked_date' => $lastDate,
            'fleet_supervisor' => $this->nullableString(
                $snapshot['sup'] ?? $snapshot['fleet_supervisor'] ?? $rider?->fleet_supervisor
            ),
            'company_name' => $this->nullableString($companyName),
            'project_name' => $this->nullableString($snapshot['project_name'] ?? $payload['project_name'] ?? null),
            'hours' => $this->formatMetric($hours),
            'orders' => $this->formatMetric($orders),
            'target' => $this->formatMetric($target),
            'alert_date' => $alertDate,
        ]);
    }

    private function normalizeDisplayDate(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        if ($value instanceof \DateTimeInterface) {
            return Carbon::instance($value)->format('d M Y');
        }
        $s = trim((string) $value);
        if ($s === '' || $s === '—' || $s === '-' || strcasecmp($s, 'n/a') === 0) {
            return null;
        }
        // Already display-formatted (e.g. "19 Nov 2026")
        if (preg_match('/^\d{1,2}\s+[A-Za-z]{3}\s+\d{4}$/', $s) === 1) {
            return $s;
        }
        try {
            return Carbon::parse($s)->format('d M Y');
        } catch (\Throwable $e) {
            return $s;
        }
    }

    private function formatMetric(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_float($value) || (is_string($value) && str_contains($value, '.'))) {
            $n = (float) $value;
            // Drop trailing .0 for whole hours
            return fmod($n, 1.0) === 0.0 ? (string) (int) $n : rtrim(rtrim(number_format($n, 2, '.', ''), '0'), '.');
        }

        return $this->nullableString($value);
    }

    private function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $s = trim((string) $value);

        return $s !== '' ? $s : null;
    }
}
