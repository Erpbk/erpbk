<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserStampSignature extends BaseModel
{
    protected $table = 'user_stamp_signatures';

    protected $fillable = [
        'company_id',
        'user_id',
        'stamp_path',
        'signature_path',
        'signatory_name',
        'designation',
        'doc_matrix',
        'other_docs',
        'is_active',
        'updated_by',
    ];

    protected $casts = [
        'doc_matrix' => 'array',
        'other_docs' => 'array',
        'is_active' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }
}
