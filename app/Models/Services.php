<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;

class Services extends Model
{
    use LogsActivity;

    public $table = 'services';

    public $fillable = [
        'name',
        'detail',
        'country',
        'parent',
        'status'
    ];

    protected $casts = [
        'name' => 'string',
        'detail' => 'string',
        'status' => 'boolean'
    ];

    public static array $rules = [
        'name' => 'required|string|max:100',
        'detail' => 'nullable|string|max:500',
        'status' => 'nullable|boolean',
        'created_at' => 'nullable',
        'updated_at' => 'nullable'
    ];

    public function parentName(){
        return $this->belongsTo(self::class,'parent');
    }
    public static function list(){
        return self::all()->pluck('name','id');
    }
    public static function Parentlist(){
        return self::where('parent',0)->pluck('name','id');
    }
    public static function Childlist($parent){
        return self::where('parent',$parent)->pluck('name','id');
    }
    public static function ParentByCountry($country){
        return self::where('parent',0)->where('country',$country)->pluck('name','id');
    }
    public static function ServiceByCountry($country){
        return self::where('country',$country)->pluck('name','id');
    }

    public function subService(){
        return $this->hasMany(Services::class,'parent');
    }
    

    
}
