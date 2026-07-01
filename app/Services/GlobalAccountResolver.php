<?php

namespace App\Services;

use App\Exceptions\GlobalAccountNotConfiguredException;
use App\Models\Accounts;
use App\Models\GlobalAccount;
use Illuminate\Support\Facades\Cache;

class GlobalAccountResolver
{
    private const CACHE_PREFIX = 'global_account:';

    private const CACHE_TTL_SECONDS = 3600;

    public function id(string $code): int
    {
        $accountId = $this->idOrNull($code);

        if ($accountId === null) {
            throw new GlobalAccountNotConfiguredException($this->labelForCode($code));
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
            if ($this->linkedAccountExists($cached)) {
                return $cached;
            }

            Cache::put(self::CACHE_PREFIX . $code, false, self::CACHE_TTL_SECONDS);

            return null;
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

        if (! $this->linkedAccountExists($accountId)) {
            Cache::put(self::CACHE_PREFIX . $code, false, self::CACHE_TTL_SECONDS);

            return null;
        }

        Cache::put(self::CACHE_PREFIX . $code, $accountId, self::CACHE_TTL_SECONDS);

        return $accountId;
    }

    public function account(string $code): Accounts
    {
        $accountId = $this->id($code);

        $account = Accounts::withoutGlobalScopes(['company', 'branch'])->find($accountId);

        if (! $account) {
            throw new GlobalAccountNotConfiguredException($this->labelForCode($code));
        }

        return $account;
    }

    /**
     * @param  array<int, string>  $codes
     */
    public function requireExists(array $codes): void
    {
        foreach (array_unique(array_filter($codes)) as $code) {
            $this->id($code);
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

    private function linkedAccountExists(int $accountId): bool
    {
        return Accounts::withoutGlobalScopes(['company', 'branch'])
            ->where('id', $accountId)
            ->exists();
    }
}
