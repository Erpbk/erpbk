<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Salik global account codes configured via global_accounts / Admin → Global Accounts.
     */
    private const SALIK_ASSET_ID = 2490;

    private const SALIK_ADMIN_CHARGES_ID = 2476;

    private const SALIK_PAYABLE_ID = 2521;

    public function up(): void
    {
        if (!Schema::hasTable('accounts')) {
            return;
        }

        $now = now();

        $this->ensureSalikAccount(
            self::SALIK_ASSET_ID,
            'Salik Asset',
            'Asset',
            ['Current Assets', 'Assets', 'Asset'],
            $now
        );

        $this->ensureSalikAccount(
            self::SALIK_PAYABLE_ID,
            'Salik Payable',
            'Liability',
            ['Current Liabilities', 'Liabilities', 'Salik', 'Liability'],
            $now
        );

        $this->ensureSalikAccount(
            self::SALIK_ADMIN_CHARGES_ID,
            'Salik Admin Charges',
            'Revenue',
            ['Revenue', 'Operating Revenue', 'Income', 'Other Revenue'],
            $now
        );
    }

    public function down(): void
    {
        // Intentionally no-op: shared head accounts may be in use.
    }

    private function ensureSalikAccount(
        int $id,
        string $name,
        string $accountType,
        array $parentNameCandidates,
        $now
    ): void {
        $parentId = $this->resolveParentId($accountType, $parentNameCandidates);

        $existingAtId = DB::table('accounts')->where('id', $id)->first();

        if ($existingAtId) {
            DB::table('accounts')->where('id', $id)->update([
                'name' => $name,
                'account_type' => $accountType,
                'parent_id' => $parentId,
                'status' => $existingAtId->status ?? 1,
                'deleted_at' => null,
                'updated_at' => $now,
            ]);

            return;
        }

        $existingUnderType = DB::table('accounts')
            ->whereNull('deleted_at')
            ->where('account_type', $accountType)
            ->where(function ($query) use ($name) {
                $query->where('name', 'like', '%Salik%')
                    ->orWhere('name', $name);
            })
            ->orderByRaw('CASE WHEN name = ? THEN 0 ELSE 1 END', [$name])
            ->first();

        if ($existingUnderType) {
            DB::table('accounts')->where('id', $existingUnderType->id)->update([
                'name' => $name,
                'account_type' => $accountType,
                'parent_id' => $parentId,
                'updated_at' => $now,
            ]);

            return;
        }

        $this->insertAccount($id, $name, $accountType, $parentId, $now);
    }

    private function resolveParentId(string $accountType, array $parentNameCandidates): ?int
    {
        foreach ($parentNameCandidates as $candidateName) {
            $parentId = DB::table('accounts')
                ->whereNull('deleted_at')
                ->where('account_type', $accountType)
                ->where('name', $candidateName)
                ->value('id');

            if ($parentId) {
                return (int) $parentId;
            }
        }

        foreach ($parentNameCandidates as $candidateName) {
            $parentId = DB::table('accounts')
                ->whereNull('deleted_at')
                ->where('name', $candidateName)
                ->value('id');

            if ($parentId) {
                return (int) $parentId;
            }
        }

        $rootParentId = DB::table('accounts')
            ->whereNull('deleted_at')
            ->where('account_type', $accountType)
            ->whereNull('parent_id')
            ->orderBy('id')
            ->value('id');

        return $rootParentId ? (int) $rootParentId : null;
    }

    private function insertAccount(int $id, string $name, string $accountType, ?int $parentId, $now): void
    {
        if (DB::table('accounts')->where('id', $id)->exists()) {
            return;
        }

        DB::table('accounts')->insert([
            'id' => $id,
            'company_id' => null,
            'branch_id' => null,
            'account_code' => 'ACCT-' . str_pad((string) $id, 5, '0', STR_PAD_LEFT),
            'name' => $name,
            'account_type' => $accountType,
            'parent_id' => $parentId,
            'ref_name' => null,
            'ref_id' => null,
            'status' => 1,
            'notes' => 'Auto-created for Salik module.',
            'opening_balance' => 0,
            'is_locked' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
};
