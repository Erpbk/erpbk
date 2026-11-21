<?php

namespace App\Http\Controllers;

use App\Http\Requests\SendWhatsAppComposeRequest;
use App\Jobs\WhatsApp\SendWhatsAppComposeJob;
use App\Models\WhatsAppMessage;
use App\Services\Notifications\CompanyNotificationChannels;
use App\Services\WhatsApp\WhatsAppConnectionService;
use App\Support\CompanyContext;
use App\Support\PhoneNumber;
use Illuminate\Http\Request;

class WhatsAppComposeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function create(Request $request, WhatsAppConnectionService $connections)
    {
        $user = $request->user();
        abort_unless($user, 403);

        $companyId = (int) CompanyContext::id();
        abort_if($companyId <= 0, 403);

        // Manual compose is available whenever the user has a usable connection.
        // Company WhatsApp channel entitlement still gates automated notifications.
        $availableConnections = $connections->getActiveAssignedConnections($user, $companyId);
        $companyConnectionCount = $connections->getActiveCompanyConnections($companyId)->count();
        $openWindows = [];
        if ($availableConnections->isNotEmpty()) {
            $cutoff = now()->subHours(24);
            $phones = WhatsAppMessage::query()
                ->where('company_id', $companyId)
                ->where('direction', WhatsAppMessage::DIRECTION_IN)
                ->where('occurred_at', '>=', $cutoff)
                ->orderByDesc('occurred_at')
                ->limit(20)
                ->pluck('peer_phone')
                ->unique()
                ->values()
                ->all();
            $openWindows = $phones;
        }

        return view('whatsapp_compose.create', [
            'whatsappConnections' => $availableConnections,
            'companyConnectionCount' => $companyConnectionCount,
            'whatsappChannelEnabled' => CompanyNotificationChannels::isEnabled(
                $companyId,
                CompanyNotificationChannels::CHANNEL_WHATSAPP
            ),
            'openWindows' => $openWindows,
            'webhookUrl' => url('/webhooks/meta/whatsapp'),
        ]);
    }

    public function send(SendWhatsAppComposeRequest $request)
    {
        $user = $request->user();
        $companyId = (int) CompanyContext::id();
        abort_if($companyId <= 0, 403);

        $validated = $request->validated();
        $to = PhoneNumber::toWhatsAppDigits($validated['to_phone']);
        if (! PhoneNumber::isValidWhatsAppDigits($to)) {
            return back()->withInput()->withErrors(['to_phone' => 'Enter a valid phone number (E.164).']);
        }

        if (($validated['mode'] ?? '') === 'text') {
            $connectionId = (int) $validated['whatsapp_connection_id'];
            $hasWindow = WhatsAppMessage::query()
                ->where('company_id', $companyId)
                ->where('peer_phone', $to)
                ->where('whatsapp_connection_id', $connectionId)
                ->where('direction', WhatsAppMessage::DIRECTION_IN)
                ->where('occurred_at', '>=', now()->subHours(24))
                ->exists();
            if (! $hasWindow) {
                return back()->withInput()->withErrors([
                    'to_phone' => 'The 24-hour messaging window is closed for this number on the selected WhatsApp line.',
                ]);
            }
        }

        $templateParams = [];
        if (($validated['mode'] ?? '') === 'template' && ! empty($validated['template_params'])) {
            $raw = preg_split('/\r\n|\r|\n|,/', (string) $validated['template_params']) ?: [];
            foreach ($raw as $param) {
                $param = trim((string) $param);
                if ($param !== '') {
                    $templateParams[] = $param;
                }
            }
        }

        SendWhatsAppComposeJob::dispatch(
            $companyId,
            (int) $user->id,
            (int) $validated['whatsapp_connection_id'],
            $to,
            (string) $validated['mode'],
            $validated['body'] ?? null,
            $validated['template_name'] ?? null,
            $validated['template_language'] ?? 'en',
            $templateParams
        )->onQueue('notifications');

        return redirect()
            ->route('settings-panel.whatsapp-compose.create', [
                'company_slug' => $request->route('company_slug'),
            ])
            ->with('success', 'WhatsApp message queued for delivery.');
    }
}
