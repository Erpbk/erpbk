<?php

namespace App\Http\Middleware;

use App\Services\DeleteRequestService;
use Closure;
use Illuminate\Http\Request;

/**
 * Blocks edit/update access to records that have a pending delete request.
 */
class BlockPendingDeletionEdits
{
    public function handle(Request $request, Closure $next)
    {
        if (! DeleteRequestService::enabled() || DeleteRequestService::isBypassing()) {
            return $next($request);
        }

        $routeName = (string) ($request->route()?->getName() ?? '');
        if ($routeName === '' || ! preg_match('/\.(edit|update)$/', $routeName)) {
            return $next($request);
        }

        $showRoute = preg_replace('/\.(edit|update)$/', '.show', $routeName);
        $id = $request->route('id')
            ?? $request->route('rider')
            ?? $request->route('employee')
            ?? $request->route('bike');

        if (is_object($id) && method_exists($id, 'getKey')) {
            $id = $id->getKey();
        }

        if (! $id) {
            return $next($request);
        }

        foreach (config('delete_approval.modules', []) as $meta) {
            if (($meta['show_route'] ?? null) !== $showRoute) {
                continue;
            }
            $modelClass = $meta['model'] ?? null;
            if (! $modelClass || ! class_exists($modelClass)) {
                break;
            }

            // Voucher edit/update routes pass trans_code, not the primary key.
            $model = null;
            if ($modelClass === \App\Models\Vouchers::class) {
                $model = $modelClass::query()
                    ->where('trans_code', $id)
                    ->orWhere('id', $id)
                    ->first();
            } else {
                $model = $modelClass::query()->find($id);
            }

            if ($model && DeleteRequestService::hasPending($model)) {
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json([
                        'message' => 'This record is Pending Deletion and cannot be edited until the delete request is reviewed.',
                    ], 423);
                }

                abort(403, 'This record is Pending Deletion and cannot be edited until the delete request is reviewed.');
            }
            break;
        }

        return $next($request);
    }
}
