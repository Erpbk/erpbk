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

            if (empty($branches)) {
                $builder->whereRaw('1 = 0');
            } else {
                $builder->whereIn('branch_id', $branches);
            }

        });
    }
}