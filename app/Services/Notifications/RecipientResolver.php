<?php

namespace App\Services\Notifications;

use App\Models\User;
use App\Services\Notifications\Recipients\ExplicitUsersStrategy;
use Illuminate\Support\Collection;

class RecipientResolver
{
    /**
     * Users who should receive an in-app inbox row (both + in-app-only panels).
     *
     * @param  array<string, mixed>  $recipientConfig
     * @param  array<string, mixed>  $context
     * @return Collection<int, User>
     */
    public function resolve(int $companyId, array $recipientConfig, array $context = []): Collection
    {
        $buckets = RecipientBuckets::normalize($recipientConfig);
        $userIds = RecipientBuckets::inAppUserIds($buckets);
        if ($userIds === []) {
            return collect();
        }

        return (new ExplicitUsersStrategy($userIds))
            ->resolve($companyId, $context)
            ->unique('id')
            ->values();
    }

    /**
     * Users selected for email-only (no inbox), keyed by id.
     *
     * @param  array<string, mixed>  $recipientConfig
     * @return Collection<int, User>
     */
    public function resolveEmailOnlyUsers(int $companyId, array $recipientConfig): Collection
    {
        $buckets = RecipientBuckets::normalize($recipientConfig);
        $userIds = array_values(array_diff(
            $buckets['email_only_user_ids'],
            $buckets['exclude_user_ids']
        ));
        if ($userIds === []) {
            return collect();
        }

        return (new ExplicitUsersStrategy($userIds))
            ->resolve($companyId, [])
            ->unique('id')
            ->values();
    }
}
