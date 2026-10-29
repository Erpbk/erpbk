<?php

namespace Tests\Unit\VoucherApproval;

use App\Http\Requests\VoucherApproval\RejectVoucherRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class RejectVoucherRequestTest extends TestCase
{
    public function test_reason_required_and_min_length(): void
    {
        $rules = (new RejectVoucherRequest())->rules();

        $short = Validator::make(['reason' => 'too short'], $rules);
        $this->assertTrue($short->fails());

        $ok = Validator::make(['reason' => 'This is a valid rejection reason'], $rules);
        $this->assertFalse($ok->fails());

        $missing = Validator::make([], $rules);
        $this->assertTrue($missing->fails());
    }
}
