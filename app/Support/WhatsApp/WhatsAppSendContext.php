<?php

namespace App\Support\WhatsApp;

/**
 * Explicit fields allowed for Meta template injection (no free-form data bag lookup).
 */
class WhatsAppSendContext
{
    public function __construct(
        public readonly ?string $rider_name = null,
        public readonly ?string $rider_code = null,
        public readonly ?string $rider_phone = null,
        public readonly ?string $days_absent = null,
        public readonly ?string $last_worked_date = null,
        public readonly ?string $fleet_supervisor = null,
        public readonly ?string $company_name = null,
        public readonly ?string $project_name = null,
        public readonly ?string $hours = null,
        public readonly ?string $orders = null,
        public readonly ?string $target = null,
        public readonly ?string $alert_date = null,
        public readonly ?string $notification_title = null,
        public readonly ?string $notification_body = null,
        public readonly ?string $cheque_number = null,
        public readonly ?string $amount = null,
        public readonly ?string $cheque_date = null,
        public readonly ?string $recipient_name = null,
        public readonly ?string $recipient_phone = null,
    ) {
    }

    /**
     * @param  array<string, mixed>  $fields
     */
    public static function fromArray(array $fields): self
    {
        return new self(
            rider_name: self::str($fields['rider_name'] ?? null),
            rider_code: self::str($fields['rider_code'] ?? null),
            rider_phone: self::str($fields['rider_phone'] ?? null),
            days_absent: self::str($fields['days_absent'] ?? null),
            last_worked_date: self::str($fields['last_worked_date'] ?? null),
            fleet_supervisor: self::str($fields['fleet_supervisor'] ?? null),
            company_name: self::str($fields['company_name'] ?? null),
            project_name: self::str($fields['project_name'] ?? null),
            hours: self::str($fields['hours'] ?? null),
            orders: self::str($fields['orders'] ?? null),
            target: self::str($fields['target'] ?? null),
            alert_date: self::str($fields['alert_date'] ?? null),
            notification_title: self::str($fields['notification_title'] ?? null),
            notification_body: self::str($fields['notification_body'] ?? null),
            cheque_number: self::str($fields['cheque_number'] ?? null),
            amount: self::str($fields['amount'] ?? null),
            cheque_date: self::str($fields['cheque_date'] ?? null),
            recipient_name: self::str($fields['recipient_name'] ?? null),
            recipient_phone: self::str($fields['recipient_phone'] ?? null),
        );
    }

    public function get(string $injectKey): ?string
    {
        if (! property_exists($this, $injectKey)) {
            return null;
        }

        $value = $this->{$injectKey};

        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }

    private static function str(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        if (is_bool($value)) {
            return $value ? 'yes' : 'no';
        }
        if ($value instanceof \DateTimeInterface) {
            return $value->format('d M Y');
        }
        if (is_scalar($value)) {
            $s = trim((string) $value);

            return $s !== '' ? $s : null;
        }

        return null;
    }
}
