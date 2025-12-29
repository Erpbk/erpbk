<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeletionCascade extends Model
{
    protected $table = 'deletion_cascades';

    protected $fillable = [
        'primary_model',
        'primary_id',
        'primary_name',
        'related_model',
        'related_id',
        'related_name',
        'relationship_type',
        'relationship_name',
        'deletion_type',
        'deleted_by',
        'deletion_reason',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user who triggered the deletion
     */
    public function deletedByUser()
    {
        return $this->belongsTo(\App\Models\User::class, 'deleted_by');
    }

    /**
     * Get all cascaded deletions for a primary record
     */
    public static function getCascadedDeletions($modelClass, $id)
    {
        return static::where('primary_model', $modelClass)
            ->where('primary_id', $id)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get the primary deletion that caused this related deletion
     */
    public static function getPrimaryDeletion($relatedModelClass, $relatedId)
    {
        return static::where('related_model', $relatedModelClass)
            ->where('related_id', $relatedId)
            ->orderBy('created_at', 'desc')
            ->first();
    }

    /**
     * Get deletion chain (all related deletions grouped by primary)
     */
    public static function getDeletionChain($modelClass, $id)
    {
        $cascades = static::getCascadedDeletions($modelClass, $id);

        return [
            'primary' => [
                'model' => $modelClass,
                'id' => $id,
                'name' => $cascades->first()->primary_name ?? null,
            ],
            'cascaded' => $cascades->groupBy('related_model')->map(function ($items, $model) {
                return [
                    'model' => $model,
                    'count' => $items->count(),
                    'records' => $items->map(function ($item) {
                        return [
                            'id' => $item->related_id,
                            'name' => $item->related_name,
                            'relationship' => $item->relationship_name,
                            'type' => $item->deletion_type,
                            'deleted_at' => $item->created_at,
                        ];
                    }),
                ];
            }),
            'total_cascaded' => $cascades->count(),
        ];
    }

    /**
     * Log a cascading deletion
     */
    public static function logCascade(
        $primaryModel,
        $primaryId,
        $primaryName,
        $relatedModel,
        $relatedId,
        $relatedName,
        $relationshipType = 'hasOne',
        $relationshipName = null,
        $deletionType = 'soft',
        $reason = null
    ) {
        return static::create([
            'primary_model' => is_object($primaryModel) ? get_class($primaryModel) : $primaryModel,
            'primary_id' => $primaryId,
            'primary_name' => $primaryName,
            'related_model' => is_object($relatedModel) ? get_class($relatedModel) : $relatedModel,
            'related_id' => $relatedId,
            'related_name' => $relatedName,
            'relationship_type' => $relationshipType,
            'relationship_name' => $relationshipName,
            'deletion_type' => $deletionType,
            'deleted_by' => auth()->id(),
            'deletion_reason' => $reason,
            'metadata' => [
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'timestamp' => now()->toIso8601String(),
            ],
        ]);
    }

    /**
     * Get summary of what was deleted
     */
    public function getSummary()
    {
        return "{$this->primary_model} '{$this->primary_name}' (#{$this->primary_id}) caused deletion of {$this->related_model} '{$this->related_name}' (#{$this->related_id})";
    }
}
