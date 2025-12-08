<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Fortify;
use Illuminate\Support\Facades\Auth;


class FortifyServiceProvider extends ServiceProvider
{
  /**
   * Register any application services.
   */
  public function register(): void
  {
    //
  }

  /**
   * Bootstrap any application services.
   */
  public function boot(): void
  {

    Fortify::authenticateUsing(function (Request $request) {
      $user = User::where('email', $request->email)->orWhere('username', $request->email)->first();

      $statusValue = strtolower((string) ($user->status ?? ''));
      $isActive = in_array($statusValue, ['1', 'true', 'active'], true);

      if ($user && $isActive && Hash::check($request->password, $user->password)) {

        if (filter_var($request->email, FILTER_VALIDATE_EMAIL)) {
          if (Auth::attempt($request->only('email', 'password'), $request->filled('remember'))) {
            return $user;
          }
        } else {
          if (Auth::attempt(['username' => $request->email, 'password' => $request->password], $request->filled('remember'))) {
            return $user;
          }
        }
      } elseif ($user && !$isActive) {
        $error['email'] = 'Your account has been deactivated. Please contact the administrator.';
        throw ValidationException::withMessages($error);
      }
    });

    /* Fortify::registerView(function () {
      return view('auth.register');
    }); */

    Fortify::loginView(function () {
      return view('auth.login');
    });
    Fortify::requestPasswordResetLinkView(function () {
      return view('auth.passwords.email');
    });
    Fortify::resetPasswordView(function () {
      return view('auth.passwords.reset');
    });

    Fortify::createUsersUsing(CreateNewUser::class);
    Fortify::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
    Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
    Fortify::resetUserPasswordsUsing(ResetUserPassword::class);

    RateLimiter::for('login', function (Request $request) {
      $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())) . '|' . $request->ip());

      return Limit::perMinute(5)->by($throttleKey);
    });

    RateLimiter::for('two-factor', function (Request $request) {
      return Limit::perMinute(5)->by($request->session()->get('login.id'));
    });
  }
}
