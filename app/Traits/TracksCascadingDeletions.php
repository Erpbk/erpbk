<?php

namespace App\Traits;

use App\Models\DeletionCascade;

trait TracksCascadingDeletions
{
    /**
     * Track a cascading deletion
     */
    protected function trackCascadeDeletion(
        $primaryModel,
        $primaryId,
        $primaryName,
        $relatedModel,
        $relatedId,
        $relatedName,
        $relationshipType = 'hasOne',
        $relationshipName = null,
        $deletionType = 'soft'
    ) {
        return DeletionCascade::logCascade(
            $primaryModel,
            $primaryId,
            $primaryName,
            $relatedModel,
            $relatedId,
            $relatedName,
            $relationshipType,
            $relationshipName,
            $deletionType
        );
    }

    /**
     * Get cascaded deletions for a record
     */
    protected function getCascadedDeletions($modelClass, $id)
    {
        return DeletionCascade::getCascadedDeletions($modelClass, $id);
    }

    /**
     * Get the primary deletion that caused a related deletion
     */
    protected function getPrimaryDeletion($relatedModelClass, $relatedId)
    {
        return DeletionCascade::getPrimaryDeletion($relatedModelClass, $relatedId);
    }

    /**
     * Get complete deletion chain with all related deletions
     */
    protected function getDeletionChain($modelClass, $id)
    {
        return DeletionCascade::getDeletionChain($modelClass, $id);
    }

    /**
     * Build a human-readable cascade deletion message
     */
    protected function buildCascadeDeletionMessage($cascades)
    {
        if ($cascades->isEmpty()) {
            return '';
        }

        $relatedCounts = [];
        foreach ($cascades as $cascade) {
            $modelName = class_basename($cascade->related_model);
            if (!isset($relatedCounts[$modelName])) {
                $relatedCounts[$modelName] = 0;
            }
            $relatedCounts[$modelName]++;
        }

        $parts = [];
        foreach ($relatedCounts as $model => $count) {
            $parts[] = "$count " . str_replace('_', ' ', \Illuminate\Support\Str::plural($model, $count));
        }

        return 'Also deleted: ' . implode(', ', $parts);
    }
}

