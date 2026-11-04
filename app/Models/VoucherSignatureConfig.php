<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VoucherSignatureConfig extends BaseModel
{
    use LogsActivity;

    protected $table = 'voucher_signature_configs';

    protected $fillable = [
        'company_id',
        'voucher_type_code',
    ];

    public function slots(): HasMany
    {
        return $this->hasMany(VoucherSignatureSlot::class, 'voucher_signature_config_id')
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function activeSlots(): HasMany
    {
        return $this->slots()->where('is_active', true);
    }

    protected static function getModuleName(): string
    {
        return 'Voucher Signature Assignment';
    }
}
