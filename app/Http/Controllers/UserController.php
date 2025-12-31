<?php

namespace App\Http\Controllers;

use App\DataTables\UserDataTable;
use App\Http\Requests\CreateUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Controllers\AppBaseController;
use App\Models\Country;
use App\Models\Departments;
use App\Repositories\UserRepository;
use App\Services\ImageService;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use App\Traits\GlobalPagination;
use Flash;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;
use App\Helpers\IConstants;
use App\Models\Activity;

class UserController extends AppBaseController
{
  use GlobalPagination;
  /** @var UserRepository $userRepository*/
  private $userRepository;

  public function __construct(UserRepository $userRepo)
  {
    $this->userRepository = $userRepo;
  }

  /**
   * Display a listing of the User.
   */
  public function index(UserDataTable $userDataTable)
  {
    /* if (
      !auth()
        ->user()
        ->hasRole(IConstants::ROLE_SUPER_ADMIN)
    ) {
      if (
        !auth()
          ->user()
          ->hasPermissionTo('user_view')
      ) {
        abort(404);
      }
    }*/


    $roles = Role::all();

    return $userDataTable->render('users.index', compact('roles'));
  }

  /**
   * Show the form for creating a new User.
   */
  public function create()
  {
    $roles = Role::where('name', '!=', IConstants::ROLE_SUPER_ADMIN)->pluck('name', 'name')->all();
    $countries = Country::countries();
    $departments = Departments::all()->pluck('name', 'id');
    return view('users.create', compact('roles', 'countries', 'departments'));
  }

  /**
   * Store a newly created User in storage.
   */
  public function store(CreateUserRequest $request)
  {
    $input = $request->all();

    $input['name'] = $input['first_name'] . ' ' . $input['last_name'];
    $input['password'] = Hash::make($input['password']);
    if (!isset($input['status'])) {
      $input['status'] = null;
    }
    $user = $this->userRepository->create($input);
    $user->assignRole($request->input('roles'));

    // Log the user creation activity
    ActivityLogger::created('Users', $user);

    Flash::success('User saved successfully.');

    return redirect(route('users.index'));
  }

  /**
   * Display the specified User.
   */
  public function show($id)
  {
    $user = $this->userRepository->find($id);

    if (empty($user)) {
      Flash::error('User not found');

      return redirect(route('users.index'));
    }

    return view('users.show', compact('user', 'activities'));
  }

  /**
   * Show the form for editing the specified User.
   */
  public function edit($id)
  {
    $user = $this->userRepository->find($id);
    $roles = Role::where('name', '!=', IConstants::ROLE_SUPER_ADMIN)->pluck('name', 'name')->all();
    $userRole = $user->roles->pluck('name', 'name')->first();
    $departments = Departments::all()->pluck('name', 'id');
    $countries = Country::countries();

    if (empty($user)) {
      Flash::error('User not found');

      return redirect(route('users.index'));
    }

    return view('users.edit', compact('user', 'roles', 'countries', 'userRole', 'departments'));
  }

  /**
   * Update the specified User in storage.
   */
  public function update($id, UpdateUserRequest $request)
  {
    $user = $this->userRepository->find($id);
    $input = $request->all();

    if (empty($user)) {
      Flash::error('User not found');

      return redirect(route('users.index'));
    }

    // Store old data for activity logging
    $oldData = $user->toArray();

    if (!isset($input['status'])) {
      $input['status'] = null;
    }
    $input['name'] = $input['first_name'] . ' ' . $input['last_name'];

    if (isset($input['password']) && !empty($input['password'])) {
      $input['password'] = Hash::make($input['password']);
    } else {
      unset($input['password']);
    }

    $user = $this->userRepository->update($input, $id);

    $user->syncRoles($request->input('roles'));

    // Log the user update activity
    ActivityLogger::updated('Users', $user, $oldData);

    Flash::success('User updated successfully.');

    return redirect(route('users.index'));
  }

  /**
   * Remove the specified User from storage.
   *
   * @throws \Exception
   */
  public function destroy($id)
  {
    $user = $this->userRepository->find($id);

    if (empty($user)) {
      Flash::error('User not found');

      return redirect(route('users.index'));
    }

    // Log the user deletion activity before deleting
    ActivityLogger::deleted('Users', $user);

    $this->userRepository->delete($id);

    Flash::success('User deleted successfully.');

    return redirect(route('users.index'));
  }

  public function profile()
  {
    $user_id = auth()->user()->id;
    $user = $this->userRepository->find($user_id);

    if (request()->post()) {
      $input = request()->all();

      request()->validate([
        'first_name' => 'required',
        'last_name' => 'required',
        'address' => 'required'
      ]);

      /* if($input['first_name'] != auth()->user()->pin){
            $error['pin'] = 'Invalid PIN';
        } */

      if (!empty($error)) {
        throw ValidationException::withMessages($error);
      }

      //$user = User::where('id',$user_id);
      $user->first_name = $input['first_name'];
      $user->last_name = $input['last_name'];
      $user->name = $input['first_name'] . ' ' . $input['last_name'];

      $user->address = $input['address'];
      $user->bio = $input['bio'];
      $user->phone = $input['phone'];

      if (isset($input['password']) && !empty($input['password'])) {
        $input['password'] = Hash::make($input['password']);
      } else {
        unset($input['password']);
      }

      if (request()->file('image_name')) {
        $image_name = request()->file('image_name');

        if (isset($image_name)) {
          $imageService = new ImageService();
          $file_name = $imageService->uploadWithSize(request(), 400, null);
          $user->image_name = $file_name;
        }
      }

      $user->save();

      return redirect()
        ->back()
        ->with('success', 'Profile updated successfully.');
    }

    //$roles = Role::pluck('name','name')->all();
    $countries = Country::countries();

    return view('users.profile', compact('user', 'countries'));
  }
}
