<?php

namespace App\Traits;

use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Trait HasColumnControl
 * 
 * This trait provides reusable functionality for implementing advanced column control
 * in data tables. It includes methods for handling column visibility, ordering, and
 * export functionality.
 * 
 * Usage:
 * 1. Use this trait in your controller
 * 2. Define getTableColumns() method
 * 3. Define getExportClass() method
 * 4. Include the column control component in your view
 */
trait HasColumnControl
{
    /**
     * Get the default table columns configuration
     * Override this method in your controller
     * 
     * @return array
     */
    protected function getTableColumns()
    {
        return [
            // Example structure:
            // ['data' => 'id', 'title' => 'ID'],
            // ['data' => 'name', 'title' => 'Name'],
            // Add your table columns here
        ];
    }

    /**
     * Get the export class name for customizable exports
     * Override this method in your controller
     * 
     * @return string
     */
    protected function getExportClass()
    {
        // Return the export class that implements customizable columns
        // Example: return \App\Exports\CustomizableUserExport::class;
        return null;
    }

    /**
     * Handle customizable export with column control
     * 
     * @param \Illuminate\Http\Request $request
     * @param string $tableIdentifier
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function exportCustomizable(Request $request, $tableIdentifier = 'default_table')
    {
        $exportClass = $this->getExportClass();

        if (!$exportClass || !class_exists($exportClass)) {
            abort(404, 'Export functionality not available');
        }

        // Get column configuration from request or user settings
        $visibleColumns = $request->input('visible_columns');
        $columnOrder = $request->input('column_order');
        $format = $request->input('format', 'excel');

        // Parse JSON strings if they exist
        if (is_string($visibleColumns)) {
            $visibleColumns = json_decode($visibleColumns, true);
        }
        if (is_string($columnOrder)) {
            $columnOrder = json_decode($columnOrder, true);
        }

        // If no column settings provided in request, get from user's saved settings
        if (empty($visibleColumns) || empty($columnOrder)) {
            $userSettings = \App\Models\UserTableSettings::getSettings(auth()->id(), $tableIdentifier);

            if ($userSettings) {
                $visibleColumns = $visibleColumns ?: $userSettings->visible_columns;
                $columnOrder = $columnOrder ?: $userSettings->column_order;
            }
        }

        // Get current filters from request
        $filters = $this->getFiltersFromRequest($request);

        // Create customizable export
        $export = new $exportClass($visibleColumns, $columnOrder, $filters);

        // Generate filename with format
        $timestamp = now()->format('Y-m-d_H-i-s');
        $modelName = $this->getModelName();
        $username = auth()->user()->name ?? auth()->user()->email ?? 'user';
        $username = preg_replace('/[^a-zA-Z0-9]/', '_', $username); // Sanitize username for filename
        $filename = "{$modelName}_export_{$username}_{$timestamp}";

        // Return appropriate format
        switch ($format) {
            case 'csv':
                return Excel::download($export, "{$filename}.csv", \Maatwebsite\Excel\Excel::CSV);
            case 'pdf':
                return Excel::download($export, "{$filename}.pdf", \Maatwebsite\Excel\Excel::DOMPDF);
            case 'excel':
            default:
                return Excel::download($export, "{$filename}.xlsx");
        }
    }

    /**
     * Extract filters from request
     * Override this method to customize filter extraction
     * 
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    protected function getFiltersFromRequest(Request $request)
    {
        // Common filter parameters
        return [
            'search' => $request->input('search'),
            'quick_search' => $request->input('quick_search'),
            // Add more filters as needed
        ];
    }

    /**
     * Get model name for filename generation
     * 
     * @return string
     */
    protected function getModelName()
    {
        $className = class_basename($this);
        return str_replace('Controller', '', $className);
    }

    /**
     * Get column control component data for views
     * 
     * @param string $tableId
     * @param string $exportRoute
     * @return array
     */
    protected function getColumnControlData($tableId = 'dataTableBuilder', $exportRoute = null)
    {
        return [
            'tableId' => $tableId,
            'tableColumns' => $this->getTableColumns(),
            'exportRoute' => $exportRoute
        ];
    }

    /**
     * Include column control in view data
     * Call this method in your index or view methods
     * 
     * @param array $data
     * @param string $tableId
     * @param string $exportRoute
     * @param string $tableIdentifier
     * @return array
     */
    protected function withColumnControl(array $data = [], $tableId = 'dataTableBuilder', $exportRoute = null, $tableIdentifier = 'default_table')
    {
        return array_merge($data, $this->getColumnControlData($tableId, $exportRoute), [
            'tableIdentifier' => $tableIdentifier
        ]);
    }

    /**
     * Get user's saved table settings
     * 
     * @param string $tableIdentifier
     * @return \App\Models\UserTableSettings|null
     */
    protected function getUserTableSettings($tableIdentifier)
    {
        return \App\Models\UserTableSettings::getSettings(auth()->id(), $tableIdentifier);
    }

    /**
     * Save user's table settings
     * 
     * @param string $tableIdentifier
     * @param array|null $visibleColumns
     * @param array|null $columnOrder
     * @param array|null $additionalSettings
     * @return \App\Models\UserTableSettings
     */
    protected function saveUserTableSettings($tableIdentifier, $visibleColumns = null, $columnOrder = null, $additionalSettings = null)
    {
        return \App\Models\UserTableSettings::saveSettings(
            auth()->id(),
            $tableIdentifier,
            $visibleColumns,
            $columnOrder,
            $additionalSettings
        );
    }

    /**
     * Reset user's table settings
     * 
     * @param string $tableIdentifier
     * @return bool
     */
    protected function resetUserTableSettings($tableIdentifier)
    {
        return \App\Models\UserTableSettings::resetSettings(auth()->id(), $tableIdentifier);
    }
}
