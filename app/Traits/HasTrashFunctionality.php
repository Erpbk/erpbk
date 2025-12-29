<?php

namespace App\Traits;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Laracasts\Flash\Flash;
use Carbon\Carbon;

trait HasTrashFunctionality
{
    /**
     * Display trashed records for this specific module
     */
    public function trash(Request $request)
    {
        $modelClass = $this->getTrashModelClass();
        $config = $this->getTrashConfig();
        
        // Check permission
        if (!auth()->user()->can('trash_view')) {
            abort(403, 'You do not have permission to access the recycle bin.');
        }

        $searchQuery = $request->get('search', '');
        
        // Query trashed records using Eloquent
        $query = $modelClass::onlyTrashed()
            ->orderBy('deleted_at', 'desc');
        
        // Apply search if provided
        if ($searchQuery) {
            $query->where(function ($q) use ($config, $searchQuery) {
                foreach ($config['display_columns'] as $column) {
                    $q->orWhere($column, 'like', '%' . $searchQuery . '%');
                }
            });
        }
        
        // Paginate results
        $trashedRecords = $query->paginate(20);
        
        return view($config['trash_view'], [
            'data' => $trashedRecords,
            'searchQuery' => $searchQuery,
            'config' => $config,
        ]);
    }

    /**
     * Restore a trashed record
     */
    public function restoreTrash($id)
    {
        $modelClass = $this->getTrashModelClass();
        $config = $this->getTrashConfig();
        
        // Check permission
        if (!auth()->user()->can('trash_restore')) {
            abort(403, 'Unauthorized action.');
        }

        $record = $modelClass::onlyTrashed()->find($id);
        
        if (!$record) {
            Flash::error($config['name'] . ' not found in trash.');
            return redirect()->back();
        }

        DB::beginTransaction();
        try {
            // Restore the record
            $record->restore();
            
            // Restore cascaded deletions if any
            $restoredItems = [];
            $cascadedDeletions = DB::table('deletion_cascades')
                ->where('primary_model', $modelClass)
                ->where('primary_id', $id)
                ->where('deletion_type', 'soft')
                ->get();

            foreach ($cascadedDeletions as $cascade) {
                $relatedModelClass = $cascade->related_model;
                
                if (class_exists($relatedModelClass)) {
                    try {
                        $relatedRecord = $relatedModelClass::onlyTrashed()->find($cascade->related_id);
                        
                        if ($relatedRecord) {
                            $relatedRecord->restore();
                            $restoredItems[] = class_basename($relatedModelClass) . ": {$cascade->related_name}";
                        }
                    } catch (\Exception $e) {
                        Log::error("Error restoring cascaded record: " . $e->getMessage());
                        continue;
                    }
                }
            }

            DB::commit();
            
            $message = $config['name'] . ' restored successfully.';
            if (!empty($restoredItems)) {
                $message .= ' (Also restored: ' . implode(', ', $restoredItems) . ')';
            }
            
            Flash::success($message);
        } catch (\Exception $e) {
            DB::rollBack();
            Flash::error('Failed to restore record: ' . $e->getMessage());
        }

        return redirect()->back();
    }

    /**
     * Permanently delete a trashed record
     */
    public function forceDestroyTrash($id)
    {
        $modelClass = $this->getTrashModelClass();
        $config = $this->getTrashConfig();
        
        // Check permission
        if (!auth()->user()->can('trash_force_delete')) {
            abort(403, 'Unauthorized action.');
        }

        $record = $modelClass::onlyTrashed()->find($id);
        
        if (!$record) {
            Flash::error($config['name'] . ' not found in trash.');
            return redirect()->back();
        }

        DB::beginTransaction();
        try {
            $deletedItems = [];
            
            // Get cascaded deletions
            $cascadedDeletions = DB::table('deletion_cascades')
                ->where('primary_model', $modelClass)
                ->where('primary_id', $id)
                ->get();

            // Permanently delete cascaded records
            foreach ($cascadedDeletions as $cascade) {
                $relatedModelClass = $cascade->related_model;
                
                if (class_exists($relatedModelClass)) {
                    try {
                        $relatedRecord = $relatedModelClass::onlyTrashed()->find($cascade->related_id);
                        
                        if ($relatedRecord) {
                            $relatedRecord->forceDelete();
                            $deletedItems[] = class_basename($relatedModelClass) . ": {$cascade->related_name}";
                        }
                    } catch (\Exception $e) {
                        Log::error("Error deleting cascaded record: " . $e->getMessage());
                        continue;
                    }
                }
            }

            // Remove cascade records
            DB::table('deletion_cascades')
                ->where('primary_model', $modelClass)
                ->where('primary_id', $id)
                ->delete();

            DB::table('deletion_cascades')
                ->where('related_model', $modelClass)
                ->where('related_id', $id)
                ->delete();

            // Permanently delete the record
            $record->forceDelete();

            DB::commit();
            
            $message = $config['name'] . ' permanently deleted.';
            if (!empty($deletedItems)) {
                $message .= ' (Also permanently deleted: ' . implode(', ', $deletedItems) . ')';
            }
            
            Flash::success($message);
        } catch (\Exception $e) {
            DB::rollBack();
            Flash::error('Failed to permanently delete record: ' . $e->getMessage());
        }

        return redirect()->back();
    }

    /**
     * Override this method in each controller to return the model class
     */
    abstract protected function getTrashModelClass();

    /**
     * Override this method in each controller to return trash configuration
     */
    abstract protected function getTrashConfig();
}

