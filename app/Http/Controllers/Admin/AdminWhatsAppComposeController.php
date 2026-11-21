<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\WhatsApp\SendWhatsAppComposeJob;
use App\Models\WhatsAppConnection;
use App\Models\WhatsAppMessage;
use App\Services\WhatsApp\WhatsAppConnectionService;
use App\Support\PhoneNumber;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminWhatsAppComposeController extends Controller
{
    public function __construct(
        private readonly WhatsAppConnectionService $connections
    ) {
    }

    public function create(Request $request)
    {
        $numbers = WhatsAppConnection::query()
            ->active()
            ->with('company:id,name')
            ->orderByDesc('is_default')
            ->orderBy('label')
            ->orderBy('id')
            ->get();

        $openWindows = [];
        if ($numbers->isNotEmpty()) {
            $cutoff = now()->subHours(24);
            $openWindows = WhatsAppMessage::query()
                ->withoutGlobalScope('company')
                ->where('direction', WhatsAppMessage::DIRECTION_IN)
                ->where('occurred_at', '>=', $cutoff)
                ->orderByDesc('occurred_at')
                ->limit(30)
                ->pluck('peer_phone')
                ->unique()
                ->values()
                ->all();
        }

        return view('admin.whatsapp_compose.create', [
            'whatsappNumbers' => $numbers,
            'openWindows' => $openWindows,
            'webhookUrl' => url('/webhooks/meta/whatsapp'),
        ]);
    }

    public function send(Request $request)
    {
        $validated = $request->validate([
            'whatsapp_connection_id' => [
                'required',
                'integer',
                Rule::exists('whatsapp_connections', 'id')->where(
                    fn ($q) => $q->where('status', WhatsAppConnection::STATUS_ACTIVE)
                ),
            ],
            'to_phone' => 'required|string|max:40',
            'mode' => 'required|in:text,template',
            'body' => 'required_if:mode,text|nullable|string|max:4096',
            'template_name' => 'required_if:mode,template|nullable|string|max:128',
            'template_language' => 'nullable|string|max:16',
            'template_params' => 'nullable|string|max:2000',
            'company_id' => 'nullable|integer|exists:companies,id',
        ]);

        $prepared = $this->connections->prepareConnectionById((int) $validated['whatsapp_connection_id']);
        if (! ($prepared['ready'] ?? false)) {
            return back()->withInput()->withErrors([
                'whatsapp_connection_id' => $prepared['message'] ?? 'You cannot use this WhatsApp number.',
            ]);
        }

        $to = PhoneNumber::toWhatsAppDigits($validated['to_phone']);
        if (! PhoneNumber::isValidWhatsAppDigits($to)) {
            return back()->withInput()->withErrors(['to_phone' => 'Enter a valid phone number (E.164).']);
        }

        /** @var WhatsAppConnection $connection */
        $connection = $prepared['connection'];
        $companyId = (int) ($validated['company_id'] ?? 0);
        if ($companyId <= 0) {
            $companyId = (int) ($connection->company_id ?? 0);
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
            0,
            (int) $validated['whatsapp_connection_id'],
            $to,
            (string) $validated['mode'],
            $validated['body'] ?? null,
            $validated['template_name'] ?? null,
            $validated['template_language'] ?? 'en',
            $templateParams,
            true
        )->onQueue('notifications');

        return redirect()
            ->route('admin.whatsapp-compose.create')
            ->with('success', __('WhatsApp message queued for delivery.'));
    }
}
