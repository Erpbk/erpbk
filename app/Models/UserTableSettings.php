<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;

class UserTableSettings extends BaseModel
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'user_id',
        'table_identifier',
        'visible_columns',
        'column_order',
        'additional_settings',
    ];

    protected $casts = [
        'visible_columns' => 'array',
        'column_order' => 'array',
        'additional_settings' => 'array',
    ];

    /**
     * Get the user that owns the settings.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get settings for a specific user and table.
     * Prefer the most recently updated row so saves/loads stay aligned when duplicates exist.
     */
    public static function getSettings($userId, $tableIdentifier)
    {
        return self::queryForUserTable($userId, $tableIdentifier)
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->first();
    }

    /**
     * Save or update settings for a user and table.
     * Always updates the canonical (latest) row and removes duplicates.
     */
    public static function saveSettings($userId, $tableIdentifier, $visibleColumns = null, $columnOrder = null, $additionalSettings = null)
    {
        $payload = [
            'visible_columns' => $visibleColumns,
            'column_order' => $columnOrder,
            'additional_settings' => $additionalSettings,
        ];

        $existing = self::getSettings($userId, $tableIdentifier);

        if ($existing) {
            self::queryForUserTable($userId, $tableIdentifier)
                ->where('id', '!=', $existing->id)
                ->delete();

            $existing->fill($payload);
            $existing->save();

            return $existing->fresh() ?? $existing;
        }

        return self::create(array_merge([
            'user_id' => $userId,
            'table_identifier' => $tableIdentifier,
        ], $payload));
    }

    /**
     * Reset settings to default for a user and table
     */
    public static function resetSettings($userId, $tableIdentifier)
    {
        return self::queryForUserTable($userId, $tableIdentifier)->delete();
    }

    /**
     * Scoped query for one user's table settings (company scope still applies via BaseModel).
     */
    protected static function queryForUserTable($userId, $tableIdentifier)
    {
        return self::where('user_id', $userId)
            ->where('table_identifier', $tableIdentifier);
    }

    /**
     * Get all visible columns for export
     */
    public function getVisibleColumnsForExport()
    {
        if (!$this->visible_columns || empty($this->visible_columns)) {
            return null; // Use default columns
        }

        return $this->visible_columns;
    }

    /**
     * Get column order for export
     */
    public function getColumnOrderForExport()
    {
        if (!$this->column_order || empty($this->column_order)) {
            return null; // Use default order
        }

        return $this->column_order;
    }
}
