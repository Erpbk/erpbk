<?php

namespace App\Models\Concerns;

use App\Services\DeleteRequestService;

/**
 * Pending-deletion helpers for soft-deletable ERP models.
 * Records stay visible in listings while a delete request is pending.
 */
trait ExcludesPendingDeletion
{
    public function isPendingDeletion(): bool
    {
        return DeleteRequestService::hasPending($this);
    }

    public function pendingDeleteRequestId(): ?int
    {
        return DeleteRequestService::pendingRequestIdFor($this);
    }
}
