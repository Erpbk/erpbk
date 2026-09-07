<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminAgreementAssignableModule extends Model
{
    protected $connection = 'mysql_admin';

    protected $table = 'admin_agreement_assignable_modules';

    protected $fillable = [
        'module_key',
        'enabled',
        'sort_order',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'sort_order' => 'integer',
    ];
}
