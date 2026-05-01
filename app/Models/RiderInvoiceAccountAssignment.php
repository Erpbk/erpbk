<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RiderInvoiceAccountAssignment extends BaseModel
{
    protected $table = 'rider_invoice_account_assignments';

    protected $fillable = [
        'company_id',
        'module_key',
        'side',
        'parent_account_id',
        'child_account_id',
    ];

    public function parentAccount(): BelongsTo
    {
        return $this->belongsTo(Accounts::class, 'parent_account_id');
    }

    public function childAccount(): BelongsTo
    {
        return $this->belongsTo(Accounts::class, 'child_account_id');
    }
}
