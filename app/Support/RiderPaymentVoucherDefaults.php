<?php

namespace App\Support;

use App\Models\Settings;
use Illuminate\Support\Facades\Cache;

/**
 * Company defaults for the Rider Payment Voucher document (title, notes, signatures).
 */
final class RiderPaymentVoucherDefaults
{
    public const TITLE_KEY = 'rider_payment_voucher_title';

    public const SHOW_INTERNAL_NOTES_KEY = 'rider_payment_show_internal_notes';

    public const INTERNAL_NOTES_LABEL_KEY = 'rider_payment_internal_notes_label';

    public const SHOW_SIGNATURE_KEY = 'rider_payment_show_signature';

    public const RIDER_SIGNATURE_LABEL_KEY = 'rider_payment_rider_signature_label';

    public const PAYER_SIGNATURE_LABEL_KEY = 'rider_payment_payer_signature_label';

    public const DEFAULT_TITLE = 'Salary Payment';

    public const DEFAULT_INTERNAL_NOTES_LABEL = 'Internal Notes';

    public const DEFAULT_RIDER_SIGNATURE_LABEL = 'Received By';

    public const DEFAULT_PAYER_SIGNATURE_LABEL = 'Paid By';

    /**
     * @return array{
     *     title: string,
     *     show_internal_notes: bool,
     *     internal_notes_label: string,
     *     show_signature: bool,
     *     rider_signature_label: string,
     *     payer_signature_label: string
     * }
     */
    public static function all(?int $companyId = null): array
    {
        return [
            'title' => self::title($companyId),
            'show_internal_notes' => self::showInternalNotes($companyId),
            'internal_notes_label' => self::internalNotesLabel($companyId),
            'show_signature' => self::showSignature($companyId),
            'rider_signature_label' => self::riderSignatureLabel($companyId),
            'payer_signature_label' => self::payerSignatureLabel($companyId),
        ];
    }

    public static function title(?int $companyId = null): string
    {
        $value = trim((string) (self::get(self::TITLE_KEY, $companyId) ?? ''));

        return $value !== '' ? $value : self::DEFAULT_TITLE;
    }

    public static function showInternalNotes(?int $companyId = null): bool
    {
        return self::bool(self::SHOW_INTERNAL_NOTES_KEY, false, $companyId);
    }

    public static function internalNotesLabel(?int $companyId = null): string
    {
        $value = trim((string) (self::get(self::INTERNAL_NOTES_LABEL_KEY, $companyId) ?? ''));

        return $value !== '' ? $value : self::DEFAULT_INTERNAL_NOTES_LABEL;
    }

    public static function showSignature(?int $companyId = null): bool
    {
        return self::bool(self::SHOW_SIGNATURE_KEY, false, $companyId);
    }

    public static function riderSignatureLabel(?int $companyId = null): string
    {
        $value = trim((string) (self::get(self::RIDER_SIGNATURE_LABEL_KEY, $companyId) ?? ''));

        return $value !== '' ? $value : self::DEFAULT_RIDER_SIGNATURE_LABEL;
    }

    public static function payerSignatureLabel(?int $companyId = null): string
    {
        $value = trim((string) (self::get(self::PAYER_SIGNATURE_LABEL_KEY, $companyId) ?? ''));

        return $value !== '' ? $value : self::DEFAULT_PAYER_SIGNATURE_LABEL;
    }

    /**
     * @param  array{
     *     title?:string|null,
     *     show_internal_notes?:bool|int|string|null,
     *     internal_notes_label?:string|null,
     *     show_signature?:bool|int|string|null,
     *     rider_signature_label?:string|null,
     *     payer_signature_label?:string|null
     * }  $data
     */
    public static function save(array $data, ?int $companyId = null): void
    {
        $companyId = $companyId ?? CompanyContext::id() ?? auth()->user()?->company_id;

        $title = trim((string) ($data['title'] ?? ''));
        self::put(self::TITLE_KEY, $title !== '' ? $title : self::DEFAULT_TITLE, $companyId);

        self::put(
            self::SHOW_INTERNAL_NOTES_KEY,
            self::toFlag($data['show_internal_notes'] ?? false),
            $companyId
        );
        $notesLabel = trim((string) ($data['internal_notes_label'] ?? ''));
        self::put(
            self::INTERNAL_NOTES_LABEL_KEY,
            $notesLabel !== '' ? $notesLabel : self::DEFAULT_INTERNAL_NOTES_LABEL,
            $companyId
        );

        self::put(
            self::SHOW_SIGNATURE_KEY,
            self::toFlag($data['show_signature'] ?? false),
            $companyId
        );
        $riderLabel = trim((string) ($data['rider_signature_label'] ?? ''));
        self::put(
            self::RIDER_SIGNATURE_LABEL_KEY,
            $riderLabel !== '' ? $riderLabel : self::DEFAULT_RIDER_SIGNATURE_LABEL,
            $companyId
        );
        $payerLabel = trim((string) ($data['payer_signature_label'] ?? ''));
        self::put(
            self::PAYER_SIGNATURE_LABEL_KEY,
            $payerLabel !== '' ? $payerLabel : self::DEFAULT_PAYER_SIGNATURE_LABEL,
            $companyId
        );

        Cache::forget('settings');
    }

    private static function bool(string $key, bool $default, ?int $companyId): bool
    {
        $raw = self::get($key, $companyId);
        if ($raw === null || $raw === '') {
            return $default;
        }

        return in_array(strtolower(trim($raw)), ['1', 'true', 'yes', 'on'], true);
    }

    private static function toFlag(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        return in_array(strtolower(trim((string) $value)), ['1', 'true', 'yes', 'on'], true) ? '1' : '0';
    }

    private static function get(string $name, ?int $companyId = null): ?string
    {
        $companyId = $companyId ?? CompanyContext::id() ?? auth()->user()?->company_id;

        $query = Settings::query()->where('name', $name);
        if ($companyId) {
            $query->where(function ($q) use ($companyId) {
                $q->where('company_id', $companyId)->orWhereNull('company_id');
            })->orderByRaw('CASE WHEN company_id IS NULL THEN 1 ELSE 0 END');
        }

        $value = $query->value('value');

        return $value !== null ? (string) $value : null;
    }

    private static function put(string $name, string $value, ?int $companyId): void
    {
        $attrs = ['name' => $name];
        if ($companyId) {
            $attrs['company_id'] = $companyId;
        }

        Settings::updateOrCreate($attrs, [
            'name' => $name,
            'value' => $value,
            'company_id' => $companyId,
        ]);
    }
}
