<?php

namespace App\Exceptions;

use Illuminate\Http\Request;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

class GlobalAccountNotConfiguredException extends RuntimeException
{
    public function __construct(string $accountLabel)
    {
        parent::__construct(self::messageFor($accountLabel));
    }

    public static function messageFor(string $accountLabel): string
    {
        return 'Contact ERP Administrator to Setup '.$accountLabel.' Account';
    }

    /**
     * JSON for API/form submits; HTML fragment for modal AJAX loads that expect HTML.
     */
    public function render(Request $request): ?Response
    {
        if ($this->wantsJsonResponse($request)) {
            return response()->json([
                'success' => false,
                'message' => $this->getMessage(),
            ], 422);
        }

        if ($request->ajax()) {
            $message = e($this->getMessage());

            return response(
                '<div class="text-center p-5 text-danger modal-load-error">'
                .'<i class="fas fa-exclamation-circle fa-3x"></i>'
                .'<p class="mt-2">'.$message.'</p>'
                .'</div>',
                422
            )->header('Content-Type', 'text/html; charset=UTF-8');
        }

        return null;
    }

    private function wantsJsonResponse(Request $request): bool
    {
        return $request->wantsJson()
            || str_contains((string) $request->header('Accept'), 'application/json');
    }
}
