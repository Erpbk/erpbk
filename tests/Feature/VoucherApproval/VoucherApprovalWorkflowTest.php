<?php

namespace Tests\Feature\VoucherApproval;

use App\Models\User;
use App\Models\VoucherApprovalEvent;
use App\Models\Vouchers;
use App\Services\VoucherApprovalService;
use App\Support\VoucherApprovalPermissions;
use App\Support\VoucherApprovalRegistry;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Integration tests — require a migrated tenant DB with voucher approval columns.
 * Skipped automatically when the schema is not present.
 */
class VoucherApprovalWorkflowTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        if (! Schema::hasTable('vouchers') || ! Schema::hasColumn('vouchers', 'approval_status')) {
            $this->markTestSkipped('Voucher approval migrations have not been run.');
        }
        if (! Schema::hasTable('voucher_approval_events')) {
            $this->markTestSkipped('voucher_approval_events table missing.');
        }
        if (! Schema::hasTable('users')) {
            $this->markTestSkipped('users table missing.');
        }
    }

    public function test_legacy_backfill_vouchers_stay_approved(): void
    {
        $legacy = Vouchers::query()
            ->where('approval_status', 'approved')
            ->whereHas('approvalEvents', fn ($q) => $q->where('action', 'legacy_backfill'))
            ->first();

        if (! $legacy) {
            $this->markTestSkipped('No legacy-backfilled voucher found (fresh DB).');
        }

        $this->assertTrue($legacy->isApprovalApproved());
    }

    public function test_unauthorized_user_gets_403_on_approve_route(): void
    {
        $user = User::query()->whereDoesntHave('roles', function ($q) {
            $q->whereIn('name', ['Super Admin', 'Administrator']);
        })->first();

        $voucher = Vouchers::query()->pendingApproval()->first()
            ?? Vouchers::query()->first();

        if (! $user || ! $voucher) {
            $this->markTestSkipped('Need a non-admin user and a voucher.');
        }

        $this->actingAs($user)
            ->post(route('voucher-approvals.approve', ['id' => $voucher->id]))
            ->assertStatus(403);
    }

    public function test_reject_without_reason_fails_validation(): void
    {
        $admin = User::query()->whereHas('roles', function ($q) {
            $q->whereIn('name', ['Super Admin', 'Administrator']);
        })->first() ?? User::query()->first();

        $voucher = Vouchers::query()->first();
        if (! $admin || ! $voucher) {
            $this->markTestSkipped('Need user + voucher.');
        }

        // Ensure pending so reject path is reachable for admins
        if (! $voucher->isPendingApproval()) {
            $voucher->forceFill(['approval_status' => 'pending_approval'])->save();
        }

        $this->actingAs($admin)
            ->post(route('voucher-approvals.reject', ['id' => $voucher->id]), ['reason' => 'short'])
            ->assertSessionHasErrors('reason');
    }

    public function test_creator_cannot_self_approve_when_configured(): void
    {
        if (! VoucherApprovalRegistry::preventSelfApproval()) {
            $this->markTestSkipped('Self-approval prevention disabled.');
        }

        $voucher = Vouchers::query()->whereNotNull('Created_By')->first();
        $creator = $voucher ? User::query()->find($voucher->Created_By) : null;
        if (! $voucher || ! $creator) {
            $this->markTestSkipped('Need voucher with Created_By.');
        }

        if ($creator->isAdmin()) {
            $this->markTestSkipped('Creator is admin (bypass).');
        }

        $voucher->forceFill(['approval_status' => 'pending_approval'])->save();

        $this->assertFalse(VoucherApprovalPermissions::canApproveVoucher($voucher, $creator));
    }

    public function test_double_approve_is_rejected(): void
    {
        $admin = User::query()->whereHas('roles', function ($q) {
            $q->whereIn('name', ['Super Admin', 'Administrator']);
        })->first();
        $voucher = Vouchers::query()->first();
        if (! $admin || ! $voucher) {
            $this->markTestSkipped('Need admin + voucher.');
        }

        $voucher->forceFill([
            'approval_status' => 'approved',
            'approved_by' => $admin->id,
            'approved_at' => now(),
            'approval_locked_at' => now(),
        ])->save();

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        app(VoucherApprovalService::class)->approve($voucher, $admin);
    }

    public function test_active_scope_hides_rejected(): void
    {
        $voucher = Vouchers::query()->first();
        if (! $voucher) {
            $this->markTestSkipped('No voucher.');
        }

        $voucher->forceFill(['approval_status' => 'rejected', 'rejection_reason' => 'Test rejection reason text'])->save();

        $this->assertFalse(
            Vouchers::query()->activeApproval()->whereKey($voucher->id)->exists()
        );
        $this->assertTrue(
            Vouchers::query()->approvalRejected()->whereKey($voucher->id)->exists()
        );
    }

    public function test_gate_denies_unauthenticated_dashboard(): void
    {
        $this->assertFalse(Gate::allows('voucher.approvalDashboard'));
    }

    public function test_permission_change_event_can_be_written(): void
    {
        $admin = User::query()->first();
        if (! $admin) {
            $this->markTestSkipped('No user.');
        }

        app(VoucherApprovalService::class)->logPermissionChange($admin, [
            'role_id' => 0,
            'changes' => [['permission' => 'voucher_approval_jv_approve', 'granted' => true]],
        ]);

        $this->assertTrue(
            VoucherApprovalEvent::query()
                ->where('action', 'permission_change')
                ->where('user_id', $admin->id)
                ->exists()
        );
    }
}
