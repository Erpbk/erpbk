<?php

namespace App\Support;

use App\Models\Settings;
use Illuminate\Support\Facades\Storage;

class AuthBranding
{
    public const LOGIN_LOGO = 'auth_login_logo';

    public const REGISTER_LOGO = 'auth_register_logo';

    public const BG_COLOR = 'auth_bg_color';

    public const BG_IMAGE = 'auth_bg_image';

    public const PANEL_TAGLINE = 'auth_panel_tagline';

    /**
     * @return array{logo_url: ?string, bg_color: string, bg_image_url: ?string, tagline: string}
     */
    public static function forPage(string $page): array
    {
        $logoKey = $page === 'register' ? self::REGISTER_LOGO : self::LOGIN_LOGO;
        $stored = Settings::query()
            ->whereIn('name', [self::LOGIN_LOGO, self::REGISTER_LOGO, self::BG_COLOR, self::BG_IMAGE, self::PANEL_TAGLINE])
            ->pluck('value', 'name');

        return [
            'logo_url' => self::publicUrl($stored->get($logoKey)),
            'bg_color' => (string) ($stored->get(self::BG_COLOR) ?: '#1e3a5f'),
            'bg_image_url' => self::publicUrl($stored->get(self::BG_IMAGE)),
            'tagline' => (string) ($stored->get(self::PANEL_TAGLINE) ?: config('app.name')),
        ];
    }

    /**
     * @return array<string, ?string>
     */
    public static function allForAdmin(): array
    {
        $stored = Settings::query()
            ->whereIn('name', [self::LOGIN_LOGO, self::REGISTER_LOGO, self::BG_COLOR, self::BG_IMAGE, self::PANEL_TAGLINE])
            ->pluck('value', 'name');

        return [
            'login_logo' => $stored->get(self::LOGIN_LOGO),
            'register_logo' => $stored->get(self::REGISTER_LOGO),
            'bg_color' => (string) ($stored->get(self::BG_COLOR) ?: '#1e3a5f'),
            'bg_image' => $stored->get(self::BG_IMAGE),
            'tagline' => (string) ($stored->get(self::PANEL_TAGLINE) ?: ''),
            'login_logo_url' => self::publicUrl($stored->get(self::LOGIN_LOGO)),
            'register_logo_url' => self::publicUrl($stored->get(self::REGISTER_LOGO)),
            'bg_image_url' => self::publicUrl($stored->get(self::BG_IMAGE)),
        ];
    }

    public static function saveSetting(string $name, ?string $value): void
    {
        if ($value === null || $value === '') {
            Settings::query()->where('name', $name)->delete();

            return;
        }

        Settings::updateOrCreate(['name' => $name], ['value' => $value]);
    }

    public static function storeUploadedLogo($file, string $settingKey): string
    {
        $path = $file->store('auth-branding', 'public');
        $previous = Settings::query()->where('name', $settingKey)->value('value');
        if ($previous && Storage::disk('public')->exists($previous)) {
            Storage::disk('public')->delete($previous);
        }
        self::saveSetting($settingKey, $path);

        return $path;
    }

    public static function storeUploadedBackground($file): string
    {
        $path = $file->store('auth-branding', 'public');
        $previous = Settings::query()->where('name', self::BG_IMAGE)->value('value');
        if ($previous && Storage::disk('public')->exists($previous)) {
            Storage::disk('public')->delete($previous);
        }
        self::saveSetting(self::BG_IMAGE, $path);

        return $path;
    }

    public static function removeBackgroundImage(): void
    {
        $previous = Settings::query()->where('name', self::BG_IMAGE)->value('value');
        if ($previous && Storage::disk('public')->exists($previous)) {
            Storage::disk('public')->delete($previous);
        }
        Settings::query()->where('name', self::BG_IMAGE)->delete();
    }

    protected static function publicUrl(?string $path): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }

        return asset('storage/' . ltrim($path, '/'));
    }
}
