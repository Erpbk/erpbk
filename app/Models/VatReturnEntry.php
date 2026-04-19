<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VatReturnEntry extends BaseModel
{
    protected $fillable = [
        'vat_return_id',
        'transaction_id',
    ];

    public function vatReturn(): BelongsTo
    {
        return $this->belongsTo(VatReturn::class);
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transactions::class);
    }
}
