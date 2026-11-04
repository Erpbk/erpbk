<?php

namespace App\Services\Notifications;

class DedupeKeyBuilder
{
    public function build(
        int $companyId,
        string $typeKey,
        string $sourceType,
        int|string|null $sourceId,
        string $triggerFingerprint
    ): string {
        return implode('|', [
            (string) $companyId,
            $typeKey,
            $sourceType,
            (string) ($sourceId ?? '0'),
            $triggerFingerprint,
        ]);
    }
}
