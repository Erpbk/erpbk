<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;

class UserTableSettings extends Model
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
     * Get settings for a specific user and table
     */
    public static function getSettings($userId, $tableIdentifier)
    {
        return self::where('user_id', $userId)
            ->where('table_identifier', $tableIdentifier)
            ->first();
    }

    /**
     * Save or update settings for a user and table
     */
    public static function saveSettings($userId, $tableIdentifier, $visibleColumns = null, $columnOrder = null, $additionalSettings = null)
    {
        return self::updateOrCreate(
            [
                'user_id' => $userId,
                'table_identifier' => $tableIdentifier,
            ],
            [
                'visible_columns' => $visibleColumns,
                'column_order' => $columnOrder,
                'additional_settings' => $additionalSettings,
            ]
        );
    }

    /**
     * Reset settings to default for a user and table
     */
    public static function resetSettings($userId, $tableIdentifier)
    {
        return self::where('user_id', $userId)
            ->where('table_identifier', $tableIdentifier)
            ->delete();
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
