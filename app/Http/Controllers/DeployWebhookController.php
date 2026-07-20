<?php

namespace App\Http\Controllers;

use App\Services\Deploy\DeployRunner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeployWebhookController extends Controller
{
    public function __invoke(Request $request, DeployRunner $deployRunner): JsonResponse
    {
        if (! config('deploy.webhook_enabled')) {
            return response()->json(['message' => 'Deploy webhook is disabled.'], 403);
        }

        $secret = config('deploy.webhook_secret');
        if (! $secret) {
            return response()->json(['message' => 'Deploy webhook secret is not configured.'], 503);
        }

        $provided = $request->header('X-Deploy-Secret') ?? $request->query('secret');
        if (! is_string($provided) || ! hash_equals($secret, $provided)) {
            return response()->json(['message' => 'Invalid deploy secret.'], 401);
        }

        $allowedIps = config('deploy.webhook_ips', []);
        if ($allowedIps !== [] && ! in_array($request->ip(), $allowedIps, true)) {
            return response()->json(['message' => 'IP not allowed.'], 403);
        }

        $deployRunner->runInBackground();

        return response()->json([
            'message' => 'Deployment started.',
            'log' => 'storage/logs/deploy.log',
        ], 202);
    }
}
