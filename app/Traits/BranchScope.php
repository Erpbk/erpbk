<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

trait BranchScope
{
    protected static function bootBranchScope()
    {
        static::addGlobalScope('branch', function (Builder $builder) {

            $user = auth()->user();
            if (!$user) {
                return;
            }

            if ($user->hasAnyRole('Administrator','Super Admin')) {
                return;
            }
            $branches = app('user_branches');

            // Qualify branch_id with table name to avoid ambiguity when query joins other tables that have branch_id
            $table = $builder->getModel()->getTable();
            $branchColumn = $table . '.branch_id';

            if (empty($branches)) {
                // User has no branches assigned: show only records with no branch (NULL) so data still visible
                $builder->whereNull($branchColumn);
            } else {
                // Include riders in user's branches OR with no branch assigned (NULL) so legacy/unassigned riders still show
                $builder->where(function ($q) use ($branches, $branchColumn) {
                    $q->whereIn($branchColumn, $branches)
                      ->orWhereNull($branchColumn);
                });
            }

        });
    }
}