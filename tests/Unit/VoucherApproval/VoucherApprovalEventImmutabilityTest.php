<?php

namespace Tests\Unit\VoucherApproval;

use App\Models\VoucherApprovalEvent;
use Tests\TestCase;

class VoucherApprovalEventImmutabilityTest extends TestCase
{
    public function test_updating_throws(): void
    {
        $event = new VoucherApprovalEvent();
        $event->exists = true;
        $event->id = 1;

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('immutable');

        $event->update(['remark' => 'tamper']);
    }

    public function test_deleting_throws(): void
    {
        $event = new VoucherApprovalEvent();
        $event->exists = true;
        $event->id = 1;

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('immutable');

        $event->delete();
    }
}
