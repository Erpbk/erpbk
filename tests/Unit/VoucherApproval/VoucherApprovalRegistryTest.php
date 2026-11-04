<?php

namespace Tests\Unit\VoucherApproval;

use App\Support\VoucherApprovalRegistry;
use Tests\TestCase;

class VoucherApprovalRegistryTest extends TestCase
{
    public function test_all_specified_voucher_types_are_registered(): void
    {
        $required = [
            'JV', 'TRF', 'PV', 'RV', 'AL', 'COD', 'PN', 'INC', 'PAY', 'VC',
            'EXP', 'LV', 'VL', 'LE', 'VR', 'RFV', 'SV', 'GV', 'RI', 'VP', 'VV',
            'BL', 'FAV', 'FDV', 'IL',
        ];

        $types = VoucherApprovalRegistry::types();
        foreach ($required as $code) {
            $this->assertArrayHasKey($code, $types, "Missing registry entry for {$code}");
            $this->assertNotEmpty($types[$code]['approve'] ?? null);
            $this->assertNotEmpty($types[$code]['reject'] ?? null);
        }
    }

    public function test_approve_and_reject_permission_names_differ(): void
    {
        foreach (VoucherApprovalRegistry::types() as $code => $def) {
            $this->assertNotSame(
                $def['approve'],
                $def['reject'],
                "Approve/reject must be distinct for {$code}"
            );
        }
    }
}
