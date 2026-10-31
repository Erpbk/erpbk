<?php

namespace App\Traits;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Schema;

/**
 * Auto-sets created_by / updated_by / deleted_by from the authenticated user.
 *
 * Override attributionCreatedByColumn() / attributionUpdatedByColumn() /
 * attributionDeletedByColumn() for legacy column names (e.g. Vouchers).
 */
trait TracksUserAttribution
{
    /** @var array<string, array<string, bool>> */
    protected static array $attributionColumnCache = [];

    protected static function bootTracksUserAttribution(): void
    {
        static::creating(function (Model $model): void {
            /** @var self&Model $model */
            $column = $model->attributionCreatedByColumn();
            if (! $model->attributionHasColumn($column)) {
                return;
            }

            if ($model->getAttribute($column) !== null && $model->getAttribute($column) !== '') {
                return;
            }

            $userId = $model->attributionUserId();
            if ($userId === null) {
                return;
            }

            $model->setAttribute($column, $userId);
        });

        static::updating(function (Model $model): void {
            /** @var self&Model $model */
            $column = $model->attributionUpdatedByColumn();
            if (! $model->attributionHasColumn($column)) {
                return;
            }

            // Soft-delete / restore touch deleted_at (and deleted_by); skip updated_by noise.
            $dirty = array_keys($model->getDirty());
            $ignored = array_filter([
                $model->getUpdatedAtColumn(),
                method_exists($model, 'getDeletedAtColumn') ? $model->getDeletedAtColumn() : null,
                $model->attributionDeletedByColumn(),
            ]);
            $meaningful = array_diff($dirty, $ignored);
            if ($meaningful === []) {
                return;
            }

            $userId = $model->attributionUserId();
            if ($userId === null) {
                return;
            }

            $model->setAttribute($column, $userId);
        });

        // SoftDeletes persists only deleted_at via a direct query. Set deleted_by
        // after a successful soft-delete so pending delete-approval aborts never stamp it.
        static::deleted(function (Model $model): void {
            /** @var self&Model $model */
            if (! static::attributionUsesSoftDeletes()) {
                return;
            }

            if (method_exists($model, 'isForceDeleting') && $model->isForceDeleting()) {
                return;
            }

            $column = $model->attributionDeletedByColumn();
            if (! $model->attributionHasColumn($column)) {
                return;
            }

            // Controllers / DeleteRequestService may have saved deleted_by before delete().
            $existing = $model->getAttribute($column);
            if ($existing !== null && $existing !== '') {
                return;
            }

            $userId = $model->attributionUserId();
            if ($userId === null) {
                return;
            }

            $model->newQueryWithoutScopes()
                ->where($model->getKeyName(), $model->getKey())
                ->update([$column => $userId]);

            $model->setAttribute($column, $userId);
        });

        if (static::attributionUsesSoftDeletes()) {
            static::restoring(function (Model $model): void {
                /** @var self&Model $model */
                $column = $model->attributionDeletedByColumn();
                if (! $model->attributionHasColumn($column)) {
                    return;
                }

                if ($model->getAttribute($column) !== null) {
                    $model->setAttribute($column, null);
                }
            });
        }
    }

    protected function attributionCreatedByColumn(): string
    {
        return 'created_by';
    }

    protected function attributionUpdatedByColumn(): string
    {
        return 'updated_by';
    }

    protected function attributionDeletedByColumn(): string
    {
        return 'deleted_by';
    }

    protected function attributionUserId(): ?int
    {
        $id = auth()->id();

        return $id !== null ? (int) $id : null;
    }

    protected static function attributionUsesSoftDeletes(): bool
    {
        return in_array(SoftDeletes::class, class_uses_recursive(static::class), true);
    }

    protected function attributionHasColumn(string $column): bool
    {
        if ($column === '') {
            return false;
        }

        $table = $this->getTable();
        $cacheKey = static::class.'|'.$table;

        if (! isset(static::$attributionColumnCache[$cacheKey])) {
            static::$attributionColumnCache[$cacheKey] = [];
        }

        if (! array_key_exists($column, static::$attributionColumnCache[$cacheKey])) {
            static::$attributionColumnCache[$cacheKey][$column] = Schema::hasTable($table)
                && Schema::hasColumn($table, $column);
        }

        return static::$attributionColumnCache[$cacheKey][$column];
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, $this->attributionCreatedByColumn());
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, $this->attributionUpdatedByColumn());
    }

    public function deletedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, $this->attributionDeletedByColumn());
    }
}
