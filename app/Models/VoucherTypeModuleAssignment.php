<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VoucherTypeModuleAssignment extends BaseModel
{
    protected $table = 'voucher_type_module_assignments';

    protected $fillable = [
        'voucher_type_id',
        'module_key',
        'can_edit',
        'can_delete',
    ];

    protected $casts = [
        'can_edit' => 'boolean',
        'can_delete' => 'boolean',
    ];

    public function voucherType(): BelongsTo
    {
        return $this->belongsTo(VoucherType::class, 'voucher_type_id');
    }
}
