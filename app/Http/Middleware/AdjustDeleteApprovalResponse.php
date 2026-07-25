<?php

namespace App\Http\Middleware;

use App\Services\DeleteRequestService;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laracasts\Flash\Flash;

class AdjustDeleteApprovalResponse
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        if (! $request->attributes->get('delete_approval_created')) {
            return $response;
        }

        $message = DeleteRequestService::pendingMessage(
            $request->attributes->get('delete_approval_request')
        );

        if ($response instanceof JsonResponse) {
            $data = $response->getData(true);
            if (is_array($data) && array_key_exists('message', $data)) {
                $data['message'] = $message;
                $response->setData($data);
            }
        }

        // Always replace any controller success flash with the single pending message.
        if (session()->has('flash_notification')) {
            session()->forget('flash_notification');
        }
        Flash::warning($message)->important();

        return $response;
    }
}
