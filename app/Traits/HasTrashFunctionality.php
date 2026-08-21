<?php

namespace App\Traits;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Support\TrashedRecordQuery;
use Laracasts\Flash\Flash;

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
        
        $query = TrashedRecordQuery::for($modelClass)
            ->orderBy('deleted_at', 'desc');

        if (! empty($config['where']) && is_array($config['where'])) {
            $query->where($config['where']);
        }
        
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

        $record = TrashedRecordQuery::find($modelClass, $id);
        
        if (!$record) {
            Flash::error($config['name'] . ' not found in trash.');
            return redirect()->back();
        }

        if (\App\Services\DeleteRequestService::hasPending($record)) {
            Flash::error('This record has a pending delete request. Resolve it from Delete Requests.');
            return redirect()->route('settings-panel.delete-requests.index');
        }

        DB::beginTransaction();
        try {
            // Restore the record
            $record->restore();

            if (\Illuminate\Support\Facades\Schema::hasColumn($record->getTable(), 'deleted_by') && $record->deleted_by) {
                $record->deleted_by = null;
                $record->save();
            }

            \App\Services\DeleteRequestService::markRestoredFromBin($record, auth()->user());
            
            // Restore cascaded deletions if any
            $restoredItems = [];
            $cascadedDeletions = \App\Support\CompanyQuery::table('deletion_cascades')
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
                            if ($relatedRecord instanceof \App\Models\SupplierInvoices) {
                                $restoredItems = array_merge($restoredItems, $relatedRecord->restoreRelatedRecords());
                            }
                        }
                    } catch (\Exception $e) {
                        Log::error("Error restoring cascaded record: " . $e->getMessage());
                        continue;
                    }
                }
            }

            \App\Support\CompanyQuery::table('deletion_cascades')
                ->where('primary_model', $modelClass)
                ->where('primary_id', $id)
                ->delete();

            \App\Support\CompanyQuery::table('deletion_cascades')
                ->where('related_model', $modelClass)
                ->where('related_id', $id)
                ->delete();

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

        $record = TrashedRecordQuery::find($modelClass, $id);
        
        if (!$record) {
            Flash::error($config['name'] . ' not found in trash.');
            return redirect()->back();
        }

        if (\App\Services\DeleteRequestService::hasPending($record)) {
            Flash::error('This record has a pending delete request. Resolve it from Delete Requests before permanent deletion.');
            return redirect()->route('settings-panel.delete-requests.index');
        }

        DB::beginTransaction();
        try {
            $deletedItems = [];

            // Get cascaded deletions
            $cascadedDeletions = \App\Support\CompanyQuery::table('deletion_cascades')
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
                            if ($relatedRecord instanceof \App\Models\SupplierInvoices) {
                                $deletedItems = array_merge($deletedItems, $relatedRecord->purgeRelatedRecords());
                            }
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
            \App\Support\CompanyQuery::table('deletion_cascades')
                ->where('primary_model', $modelClass)
                ->where('primary_id', $id)
                ->delete();

            \App\Support\CompanyQuery::table('deletion_cascades')
                ->where('related_model', $modelClass)
                ->where('related_id', $id)
                ->delete();

            // Permanently delete the record
            \App\Services\DeleteRequestService::markPermanentlyDeletedFromBin($record, auth()->user());
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

