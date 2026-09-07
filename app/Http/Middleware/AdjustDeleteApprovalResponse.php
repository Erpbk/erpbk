<?php

namespace App\Http\Middleware;

use App\Services\DeleteRequestService;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Laracasts\Flash\Flash;

class AdjustDeleteApprovalResponse
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        $queued = (bool) $request->attributes->get('delete_approval_created');
        $wantsJson = $this->wantsJson($request);

        if ($queued) {
            $message = DeleteRequestService::pendingMessage(
                $request->attributes->get('delete_approval_request')
            );

            if ($response instanceof JsonResponse) {
                $data = $response->getData(true);
                if (! is_array($data)) {
                    $data = [];
                }
                $data['message'] = $message;
                $data['queued'] = true;
                $data['success'] = $data['success'] ?? true;
                $response->setData($data);
            } elseif ($wantsJson && $response instanceof RedirectResponse) {
                $this->clearFlash();
                Flash::warning($message)->important();

                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'queued' => true,
                ]);
            }

            // Always replace any controller success flash with the single pending message.
            $this->clearFlash();
            Flash::warning($message)->important();

            return $response;
        }

        if ($wantsJson && $this->isDeleteRequest($request)) {
            if ($response instanceof RedirectResponse) {
                return $this->jsonFromFlashRedirect($response);
            }

            if ($response instanceof JsonResponse) {
                $data = $response->getData(true);
                if (is_array($data) && ! array_key_exists('queued', $data)) {
                    $data['queued'] = false;
                    $response->setData($data);
                }
            }
        }

        return $response;
    }

    private function wantsJson(Request $request): bool
    {
        if ($request->ajax() || $request->wantsJson() || $request->expectsJson()) {
            return true;
        }

        $accept = (string) $request->header('Accept', '');

        return str_contains($accept, 'application/json');
    }

    private function isDeleteRequest(Request $request): bool
    {
        if ($request->isMethod('DELETE')) {
            return true;
        }

        $method = strtoupper((string) $request->input('_method', ''));
        if ($request->isMethod('POST') && $method === 'DELETE') {
            return true;
        }

        // Legacy soft-delete routes: GET .../delete/{id}
        if ($request->isMethod('GET') && preg_match('#/(?:delete|destroy)(?:/|$)#i', $request->path())) {
            return true;
        }

        return false;
    }

    private function jsonFromFlashRedirect(RedirectResponse $response): JsonResponse
    {
        $messages = collect(session('flash_notification', []));
        $this->clearFlash();

        $last = $messages->last();
        $text = '';
        $level = 'success';

        if (is_object($last)) {
            $text = (string) ($last->message ?? '');
            $level = (string) ($last->level ?? 'success');
        } elseif (is_array($last)) {
            $text = (string) ($last['message'] ?? '');
            $level = (string) ($last['level'] ?? 'success');
        }

        if ($text === '') {
            $text = 'Record moved to Recycle Bin.';
        }

        if (in_array($level, ['danger', 'error'], true)) {
            return response()->json([
                'success' => false,
                'message' => $text,
                'queued' => false,
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => $text,
            'queued' => false,
        ]);
    }

    private function clearFlash(): void
    {
        if (session()->has('flash_notification')) {
            session()->forget('flash_notification');
        }
    }
}
