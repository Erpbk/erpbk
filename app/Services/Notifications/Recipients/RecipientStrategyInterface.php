<?php

namespace App\Services\Notifications\Recipients;

use Illuminate\Support\Collection;

interface RecipientStrategyInterface
{
    /**
     * @param  array<string, mixed>  $context
     * @return Collection<int, \App\Models\User>
     */
    public function resolve(int $companyId, array $context = []): Collection;
}
