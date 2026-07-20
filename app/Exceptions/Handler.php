<?php

namespace App\Exceptions;

use App\Support\CompanyAuthRedirect;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of exception types with their corresponding custom log levels.
     *
     * @var array<class-string<\Throwable>, \Psr\Log\LogLevel::*>
     */
    protected $levels = [
        //
    ];

    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<\Throwable>>
     */
    protected $dontReport = [
        GlobalAccountNotConfiguredException::class,
    ];

    /**
     * A list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Throwable  $e
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Throwable
     */
    public function render($request, Throwable $e)
    {
        // Handle authentication exceptions
        if ($e instanceof AuthenticationException) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Unauthenticated.'], 401);
            }
            return redirect()->guest(CompanyAuthRedirect::url($request));
        }

        // Handle throttle exceptions
        if ($e instanceof ThrottleRequestsException) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Too Many Requests.'], 429);
            }
            return back()->with('error', 'Too many requests. Please try again later.');
        }

        if ($e instanceof GlobalAccountNotConfiguredException) {
            if ($json = $e->render($request)) {
                return $json;
            }

            if ($request->routeIs('LicenseExpense.*')) {
                $slug = $request->route('company_slug') ?? session('company_slug');

                return redirect()
                    ->route('LicenseExpense.index', ['company_slug' => $slug])
                    ->with('error', $e->getMessage());
            }

            if ($request->routeIs('BikeRegistration.*')) {
                $slug = $request->route('company_slug') ?? session('company_slug');

                return redirect()
                    ->route('BikeRegistration.index', ['company_slug' => $slug])
                    ->with('error', $e->getMessage());
            }

            if ($request->routeIs('VisaExpense.*', 'Installments.*')) {
                return redirect()
                    ->route('VisaExpense.index')
                    ->with('error', $e->getMessage());
            }

            return back()->with('error', $e->getMessage())->withInput();
        }

        return parent::render($request, $e);
    }
}
