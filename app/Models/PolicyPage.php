<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PolicyPage extends Model
{
    use SoftDeletes;

    protected $connection = 'mysql_admin';

    protected $table = 'policy_pages';

    protected $fillable = [
        'key',
        'title',
        'content',
        'updated_by',
    ];
}

