<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;

class Departments extends Model
{
    use LogsActivity;

    public $table = 'departments';

    public $fillable = [
        'name',
        'detail',
        'status'
    ];

    protected $casts = [
        'name' => 'string',
        'detail' => 'string',
        'status' => 'boolean'
    ];

    public static array $rules = [
        'name' => 'nullable|string|max:100',
        'detail' => 'nullable|string|max:500',
        'status' => 'nullable|boolean',
        'created_at' => 'nullable',
        'updated_at' => 'nullable'
    ];

    public static function list(){
        return self::all()->pluck('name','id');
    }
}
