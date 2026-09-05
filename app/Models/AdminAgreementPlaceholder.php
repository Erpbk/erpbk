<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminAgreementPlaceholder extends Model
{
    protected $connection = 'mysql_admin';

    protected $table = 'admin_agreement_placeholders';

    protected $fillable = [
        'module_key',
        'placeholder',
        'description',
        'group_label',
        'source_key',
        'sort_order',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];
}
