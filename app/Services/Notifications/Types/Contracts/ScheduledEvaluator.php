<?php

namespace App\Services\Notifications\Types\Contracts;

use App\Models\NotificationRule;

interface ScheduledEvaluator
{
    /**
     * Yield candidate payloads for the engine (without writing).
     *
     * @return iterable<int, array{
     *   source:\Illuminate\Database\Eloquent\Model,
     *   title:string,
     *   body:string,
     *   severity?:string,
     *   action_url?:?string,
     *   data?:array,
     *   dedupe_context:array<string,mixed>,
     *   recipient_context?:array<string,mixed>
     * }>
     */
    public function evaluate(int $companyId, NotificationRule $rule): iterable;
}
