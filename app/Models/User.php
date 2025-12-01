<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use App\Traits\LogsActivity;

class User extends Authenticatable
{
  use HasApiTokens, HasFactory, Notifiable, HasRoles, LogsActivity;

  /**
   * The attributes that are mass assignable.
   *
   * @var array<int, string>
   */

  protected $fillable = [
    'name',
    'first_name',
    'last_name',
    'phone',
    'address',
    'city',
    'country',
    'bio',
    'image_name',
    'email',
    'password',
    'username',
    'type',
    'department_id',
    'status',
  ];

  public static array $rules = [
    'first_name' => 'required|string|max:255',
    'last_name' => 'nullable|string|max:255',
    'email' => 'nullable|string|max:255|email|unique:users',
    'username' => 'nullable|string|max:255|unique:users',
    'password' => 'min:6|string|required|confirmed',
    'address' => 'nullable|string|max:255',
    'bio' => 'nullable|string|max:255',
    'image_name' => 'nullable|string|max:100',
    'phone' => 'nullable|string|max:50',
    'roles' => 'required',
  ];

  /**
   * The attributes that should be hidden for serialization.
   *
   * @var array<int, string>
   */
  protected $hidden = ['password', 'remember_token'];

  /**
   * The attributes that should be cast.
   *
   * @var array<string, string>
   */
  protected $casts = [
    'email_verified_at' => 'datetime',
    'password' => 'hashed',
  ];


  public function department()
  {
    return $this->belongsTo(Departments::class, 'department_id', 'id');
  }
}
