<?php

namespace App\Support\WhatsApp;

use App\Models\Settings;
use App\Models\User;

/**
 * Per-user WhatsApp UI view settings (alerts drawer + inbox).
 * Stored in settings.name = whatsapp_ui_prefs_user_{id} as JSON.
 */
class WhatsAppUiPreferences
{
    /**
     * @return array{
     *   accent: string,
     *   bubble: string,
     *   chat: string,
     *   doodle: bool,
     *   panel: string,
     *   head: string,
     *   lw: int,
     *   ns: int,
     *   av: int,
     *   rh: int,
     *   mf: int,
     *   mw: int
     * }
     */
    public static function defaults(): array
    {
        return [
            'accent' => '#00a884',
            'bubble' => '#d9fdd3',
            'chat' => '#efeae2',
            'doodle' => true,
            'panel' => '#ffffff',
            'head' => '#ffffff',
            'lw' => 34,
            'ns' => 17,
            'av' => 50,
            'rh' => 74,
            'mf' => 15,
            'mw' => 62,
        ];
    }

    public static function settingNameForUser(int $userId): string
    {
        return 'whatsapp_ui_prefs_user_'.$userId;
    }

    /**
     * @return array<string, mixed>
     */
    public static function forUser(User $user): array
    {
        $defaults = self::defaults();
        $raw = Settings::query()
            ->where('name', self::settingNameForUser((int) $user->id))
            ->value('value');

        if ($raw === null || $raw === '') {
            return $defaults;
        }

        $decoded = json_decode((string) $raw, true);
        if (! is_array($decoded)) {
            return $defaults;
        }

        return self::normalize(array_merge($defaults, $decoded));
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public static function saveForUser(User $user, array $input): array
    {
        $prefs = self::normalize(array_merge(self::defaults(), $input));

        Settings::updateOrCreate(
            ['name' => self::settingNameForUser((int) $user->id)],
            ['value' => json_encode($prefs)]
        );

        return $prefs;
    }

    /**
     * @param  array<string, mixed>  $prefs
     * @return array<string, mixed>
     */
    public static function normalize(array $prefs): array
    {
        $defaults = self::defaults();
        $out = $defaults;

        foreach (['accent', 'bubble', 'chat', 'panel', 'head'] as $colorKey) {
            $v = isset($prefs[$colorKey]) ? trim((string) $prefs[$colorKey]) : '';
            $out[$colorKey] = preg_match('/^#[0-9A-Fa-f]{6}$/', $v) ? strtolower($v) : $defaults[$colorKey];
        }

        $out['doodle'] = filter_var($prefs['doodle'] ?? true, FILTER_VALIDATE_BOOLEAN);

        $out['lw'] = self::clampInt($prefs['lw'] ?? $defaults['lw'], 18, 65, (int) $defaults['lw']);
        $out['ns'] = self::clampInt($prefs['ns'] ?? $defaults['ns'], 12, 24, (int) $defaults['ns']);
        $out['av'] = self::clampInt($prefs['av'] ?? $defaults['av'], 30, 72, (int) $defaults['av']);
        $out['rh'] = self::clampInt($prefs['rh'] ?? $defaults['rh'], 50, 110, (int) $defaults['rh']);
        $out['mf'] = self::clampInt($prefs['mf'] ?? $defaults['mf'], 11, 22, (int) $defaults['mf']);
        $out['mw'] = self::clampInt($prefs['mw'] ?? $defaults['mw'], 35, 95, (int) $defaults['mw']);

        return $out;
    }

    private static function clampInt(mixed $value, int $min, int $max, int $fallback): int
    {
        if (! is_numeric($value)) {
            return $fallback;
        }

        return max($min, min($max, (int) round((float) $value)));
    }
}
