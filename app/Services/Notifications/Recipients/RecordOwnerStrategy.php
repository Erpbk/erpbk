<?php

namespace App\Services\Notifications\Recipients;

use App\Models\User;
use Illuminate\Support\Collection;

class RecordOwnerStrategy implements RecipientStrategyInterface
{
    public function resolve(int $companyId, array $context = []): Collection
    {
        $ownerId = $context['created_by'] ?? $context['owner_id'] ?? null;
        if (! $ownerId) {
            return collect();
        }

        $user = User::query()
            ->where('company_id', $companyId)
            ->where('id', (int) $ownerId)
            ->first();

        return $user ? collect([$user]) : collect();
    }
}
