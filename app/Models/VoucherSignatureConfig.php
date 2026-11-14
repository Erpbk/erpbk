<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VoucherSignatureConfig extends BaseModel
{
    use LogsActivity;

    public const POSITION_BOTTOM = 'bottom';

    public const POSITION_AFTER = 'after';

    protected $table = 'voucher_signature_configs';

    protected $fillable = [
        'company_id',
        'voucher_type_code',
        'header_settings',
        'signature_position',
    ];

    protected $casts = [
        'header_settings' => 'array',
    ];

    public function slots(): HasMany
    {
        return $this->hasMany(VoucherSignatureSlot::class, 'voucher_signature_config_id')
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function activeSlots(): HasMany
    {
        return $this->slots()->where('is_active', true);
    }

    /**
     * Default print header layout (matches Signature & Header demo).
     *
     * @return array{align: string, height: int, logo: int, name: float, det: float, line: bool, on: array<string, int>}
     */
    public static function defaultHeaderSettings(): array
    {
        return [
            'align' => 'left',
            'height' => 155,
            'logo' => 155,
            'name' => 25,
            'det' => 11.5,
            'line' => true,
            'on' => [
                'tel' => 1,
                'email' => 1,
                'addr' => 1,
                'trn' => 1,
                'web' => 0,
                'vat' => 0,
                'po' => 0,
            ],
        ];
    }

    /**
     * @param  array<string, mixed>|null  $stored
     * @return array{align: string, height: int, logo: int, name: float, det: float, line: bool, on: array<string, int>}
     */
    public static function normalizeHeaderSettings(?array $stored): array
    {
        $defaults = static::defaultHeaderSettings();
        if (!is_array($stored) || $stored === []) {
            return $defaults;
        }

        $align = in_array(($stored['align'] ?? ''), ['left', 'center', 'right'], true)
            ? (string) $stored['align']
            : $defaults['align'];

        $on = $defaults['on'];
        foreach (array_keys($on) as $key) {
            $on[$key] = !empty($stored['on'][$key]) ? 1 : 0;
        }

        return [
            'align' => $align,
            'height' => max(80, min(220, (int) ($stored['height'] ?? $defaults['height']))),
            'logo' => max(60, min(220, (int) ($stored['logo'] ?? $defaults['logo']))),
            'name' => max(14, min(36, (float) ($stored['name'] ?? $defaults['name']))),
            'det' => max(9, min(16, (float) ($stored['det'] ?? $defaults['det']))),
            'line' => array_key_exists('line', $stored)
                ? (bool) $stored['line']
                : (bool) $defaults['line'],
            'on' => $on,
        ];
    }

    public function resolvedHeaderSettings(): array
    {
        return static::normalizeHeaderSettings($this->header_settings);
    }

    public function resolvedSignaturePosition(): string
    {
        $pos = strtolower(trim((string) ($this->signature_position ?: self::POSITION_BOTTOM)));

        return $pos === self::POSITION_AFTER ? self::POSITION_AFTER : self::POSITION_BOTTOM;
    }

    /**
     * @return list<string>
     */
    public static function signaturePositions(): array
    {
        return [self::POSITION_BOTTOM, self::POSITION_AFTER];
    }

    protected static function getModuleName(): string
    {
        return 'Voucher Signature Assignment';
    }
}
