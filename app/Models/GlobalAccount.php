<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GlobalAccount extends Model
{
    public $table = 'global_accounts';

    protected $fillable = [
        'code',
        'label',
        'description',
        'account_id',
        'account_type',
        'is_active',
    ];

    protected $casts = [
        'code' => 'string',
        'label' => 'string',
        'description' => 'string',
        'account_type' => 'string',
        'is_active' => 'boolean',
    ];

    public static array $rules = [
        'code' => 'required|string|max:100|regex:/^[A-Z][A-Z0-9_]*$/',
        'label' => 'required|string|max:150',
        'description' => 'nullable|string',
        'account_id' => 'nullable|exists:accounts,id',
        'account_type' => 'nullable|string|max:50',
        'is_active' => 'nullable|boolean',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Accounts::class, 'account_id')
            ->withoutGlobalScopes(['company', 'branch']);
    }
}
