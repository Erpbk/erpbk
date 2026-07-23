<?php

namespace App\Support;

use App\Services\DeleteRequestService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;

class TrashedRecordQuery
{
    /**
     * Company-scoped soft-deleted records for recycle bin (branch filter excluded).
     * Excludes rows that still have a pending delete-approval request.
     */
    public static function for(string $modelClass): Builder
    {
        $query = $modelClass::query()
            ->onlyTrashed()
            ->withoutGlobalScope('branch');

        $pendingIds = DeleteRequestService::pendingIdsFor($modelClass);
        if ($pendingIds !== []) {
            $query->whereNotIn((new $modelClass)->getQualifiedKeyName(), $pendingIds);
        }

        if (! CompanyContext::shouldApplyScope()) {
            return $query;
        }

        $companyId = CompanyContext::id();
        if ($companyId === null) {
            return $query->whereRaw('0 = 1');
        }

        $instance = new $modelClass();
        $table = $instance->getTable();
        $connection = $instance->getConnectionName() ?: config('database.default');

        if (! Schema::connection($connection)->hasColumn($table, 'company_id')) {
            return $query;
        }

        $companyColumn = $instance->qualifyColumn('company_id');

        return $query
            ->withoutGlobalScope('company')
            ->where($companyColumn, $companyId)
            ->whereNotNull($companyColumn);
    }

    public static function find(string $modelClass, $id)
    {
        return static::for($modelClass)->find($id);
    }
}
