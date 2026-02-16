<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use App\Traits\LogsActivity;

class Settings extends Model
{
    use HasFactory, LogsActivity;
    protected $table = "settings";
    protected $fillable = [
        'name',
        'value'
    ];

    /**
     * Get a single menu label by key (stored value or default).
     */
    public static function getMenuLabel(string $key): string
    {
        $labels = self::getMenuLabels();
        return $labels[$key] ?? config('menu_labels.defaults.' . $key, $key);
    }

    /**
     * Get all menu labels (stored overrides merged with config defaults).
     * Cached per request to avoid repeated DB queries.
     */
    public static function getMenuLabels(): array
    {
        return Cache::remember('erp_menu_labels', 300, function () {
            $defaults = config('menu_labels.defaults', []);
            $stored = self::where('name', 'like', 'menu_label_%')
                ->pluck('value', 'name');
            $overrides = [];
            foreach ($stored as $name => $value) {
                $overrides[str_replace('menu_label_', '', $name)] = $value;
            }
            return array_merge($defaults, $overrides);
        });
    }

    /**
     * Clear menu labels cache (call after saving labels in Settings).
     */
    public static function clearMenuLabelsCache(): void
    {
        Cache::forget('erp_menu_labels');
    }
}
