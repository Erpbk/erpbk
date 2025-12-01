<?php

namespace App\Http\Controllers;

use App\Models\UserTableSettings;
use Illuminate\Http\Request;
use App\Traits\GlobalPagination;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class UserTableSettingsController extends Controller
{
    /**
     * Get user's table settings
     */
    public function getSettings(Request $request): JsonResponse
    {
        $tableIdentifier = $request->input('table_identifier');

        if (!$tableIdentifier) {
            return response()->json(['error' => 'Table identifier is required'], 400);
        }

        $settings = UserTableSettings::getSettings(Auth::id(), $tableIdentifier);

        if (!$settings) {
            return response()->json([
                'success' => true,
                'data' => [
                    'visible_columns' => null,
                    'column_order' => null,
                    'additional_settings' => null,
                ],
                'message' => 'No saved settings found, using defaults'
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'visible_columns' => $settings->visible_columns,
                'column_order' => $settings->column_order,
                'additional_settings' => $settings->additional_settings,
            ]
        ]);
    }

    /**
     * Save user's table settings
     */
    public function saveSettings(Request $request): JsonResponse
    {
        $request->validate([
            'table_identifier' => 'required|string|max:255',
            'visible_columns' => 'nullable|array',
            'column_order' => 'nullable|array',
            'additional_settings' => 'nullable|array',
        ]);

        try {
            $settings = UserTableSettings::saveSettings(
                Auth::id(),
                $request->input('table_identifier'),
                $request->input('visible_columns'),
                $request->input('column_order'),
                $request->input('additional_settings')
            );

            return response()->json([
                'success' => true,
                'message' => 'Settings saved successfully',
                'data' => [
                    'visible_columns' => $settings->visible_columns,
                    'column_order' => $settings->column_order,
                    'additional_settings' => $settings->additional_settings,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to save settings: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reset user's table settings to default
     */
    public function resetSettings(Request $request): JsonResponse
    {
        $request->validate([
            'table_identifier' => 'required|string|max:255',
        ]);

        try {
            UserTableSettings::resetSettings(Auth::id(), $request->input('table_identifier'));

            return response()->json([
                'success' => true,
                'message' => 'Settings reset to default successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to reset settings: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all user's table settings (for debugging or admin purposes)
     */
    public function getAllSettings(): JsonResponse
    {
        $settings = UserTableSettings::where('user_id', Auth::id())->get();

        return response()->json([
            'success' => true,
            'data' => $settings->map(function ($setting) {
                return [
                    'table_identifier' => $setting->table_identifier,
                    'visible_columns' => $setting->visible_columns,
                    'column_order' => $setting->column_order,
                    'additional_settings' => $setting->additional_settings,
                    'updated_at' => $setting->updated_at,
                ];
            })
        ]);
    }
}
