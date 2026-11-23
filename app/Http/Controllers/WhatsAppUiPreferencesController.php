<?php

namespace App\Http\Controllers;

use App\Support\WhatsApp\WhatsAppUiPreferences;
use Illuminate\Http\Request;

class WhatsAppUiPreferencesController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function show(Request $request)
    {
        $user = $request->user();
        abort_unless($user, 403);

        return response()->json([
            'prefs' => WhatsAppUiPreferences::forUser($user),
            'defaults' => WhatsAppUiPreferences::defaults(),
        ]);
    }

    public function update(Request $request)
    {
        $user = $request->user();
        abort_unless($user, 403);

        $payload = $request->input('prefs', $request->all());
        if (! is_array($payload)) {
            $payload = [];
        }

        $prefs = WhatsAppUiPreferences::saveForUser($user, $payload);

        return response()->json([
            'ok' => true,
            'prefs' => $prefs,
        ]);
    }
}
