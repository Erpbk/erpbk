<?php

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
  use PasswordValidationRules;

  /**
   * Validate and create a newly registered user.
   *
   * @param  array<string, string>  $input
   */
  public function create(array $input): User
  {
    Validator::make($input, [
      'name' => ['required', 'string', 'max:255'],
      'email' => [
        'required',
        'string',
        'email',
        'max:255',
        Rule::unique(User::class),
      ],
      'username' => [
        'required',
        'string',
        'min:6',
        'max:255',
        Rule::unique(User::class),
      ],
      'password' => [
        'required',
        'min:6',
        /* 'regex:/^.*(?=.{3,})(?=.*[a-zA-Z])(?=.*[0-9])(?=.*[\d\x])(?=.*[!$#%]).*$/', */
        'confirmed'
      ]
    ])->validate();

    $user = User::create([
      'name' => $input['name'],
      /*    'frst_name' => $input['frist_name'],
         'last_name' => $input['last_name'],
         'phone' => $input['phone'],
         'address' => $input['address'],
         'city' => $input['city'],
         'country' => $input['country'],   */
      'status' => 1,
      'email' => $input['email'],
      'username' => $input['username'],
      'password' => Hash::make($input['password']),
    ]);
    $user->assignRole('User');

    return $user;
  }
}
