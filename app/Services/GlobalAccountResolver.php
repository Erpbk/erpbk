<?php

namespace App\Services;

use App\Models\Accounts;
use App\Models\GlobalAccount;
use Illuminate\Support\Facades\Cache;
use RuntimeException;

class GlobalAccountResolver
{
    private const CACHE_PREFIX = 'global_account:';

    private const CACHE_TTL_SECONDS = 3600;

    public function id(string $code): int
    {
        $accountId = $this->idOrNull($code);

        if ($accountId === null) {
            throw new RuntimeException("Global account [{$code}] is not configured or inactive.");
        }

        return $accountId;
    }

    public function idOrNull(string $code): ?int
    {
        $cached = Cache::get(self::CACHE_PREFIX . $code);

        if ($cached === false) {
            return null;
        }

        if (is_int($cached)) {
            return $cached;
        }

        $row = GlobalAccount::query()
            ->where('code', $code)
            ->where('is_active', true)
            ->whereNotNull('account_id')
            ->first(['account_id']);

        if (! $row) {
            Cache::put(self::CACHE_PREFIX . $code, false, self::CACHE_TTL_SECONDS);

            return null;
        }

        $accountId = (int) $row->account_id;
        Cache::put(self::CACHE_PREFIX . $code, $accountId, self::CACHE_TTL_SECONDS);

        return $accountId;
    }

    public function account(string $code): ?Accounts
    {
        $accountId = $this->idOrNull($code);

        if ($accountId === null) {
            return null;
        }

        return Accounts::withoutGlobalScopes(['company', 'branch'])->find($accountId);
    }

    /**
     * @param  array<int, string>  $codes
     */
    public function requireExists(array $codes): void
    {
        foreach (array_unique(array_filter($codes)) as $code) {
            $accountId = $this->idOrNull($code);

            if ($accountId === null || ! Accounts::withoutGlobalScopes(['company', 'branch'])->where('id', $accountId)->exists()) {
                $label = $this->labelForCode($code);
                throw new RuntimeException("{$label} account does not exist.");
            }
        }
    }

    /**
     * @param  array<int, string>  $codes
     * @return array<int, string>  resolved account id => label
     */
    public function labelsForCodes(array $codes): array
    {
        $labels = [];

        foreach (array_unique(array_filter($codes)) as $code) {
            $accountId = $this->idOrNull($code);

            if ($accountId !== null) {
                $labels[$accountId] = $this->labelForCode($code);
            }
        }

        return $labels;
    }

    public function labelForCode(string $code): string
    {
        $row = GlobalAccount::query()->where('code', $code)->first(['label']);

        return $row?->label ?? $code;
    }

    public function flushCache(?string $code = null): void
    {
        if ($code !== null) {
            Cache::forget(self::CACHE_PREFIX . $code);

            return;
        }

        GlobalAccount::query()->pluck('code')->each(function (string $code): void {
            Cache::forget(self::CACHE_PREFIX . $code);
        });
    }
}
