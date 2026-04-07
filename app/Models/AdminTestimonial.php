<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AdminTestimonial extends Model
{
    use SoftDeletes;

    protected $connection = 'mysql_admin';

    protected $table = 'testimonials';

    protected $fillable = [
        'name',
        'title',
        'content',
        'status',
        'rating',
        'published_at',
        'created_by',
    ];

    protected $casts = [
        'published_at' => 'datetime',
    ];
}

