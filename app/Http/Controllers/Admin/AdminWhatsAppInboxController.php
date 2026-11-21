<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\WhatsApp\SendWhatsAppComposeJob;
use App\Models\WhatsAppConnection;
use App\Models\WhatsAppMessage;
use App\Services\WhatsApp\WhatsAppConnectionService;
use App\Services\WhatsApp\WhatsAppInboxService;
use App\Support\PhoneNumber;
use Illuminate\Http\Request;

class AdminWhatsAppInboxController extends Controller
{
    public function index(Request $request, WhatsAppInboxService $inbox)
    {
        $conversations = $inbox->listDefaultConversations($request->query('q'));

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['conversations' => $conversations]);
        }

        return view('admin.whatsapp_inbox.index', [
            'conversations' => $conversations,
        ]);
    }

    public function thread(Request $request, WhatsAppInboxService $inbox)
    {
        $peerPhone = (string) $request->route('peerPhone', '');
        abort_unless($peerPhone !== '', 404);

        $afterId = $request->query('after_id') ? (int) $request->query('after_id') : null;

        return response()->json($inbox->defaultThreadPayload($peerPhone, $afterId));
    }

    public function send(Request $request, WhatsAppConnectionService $connections, WhatsAppInboxService $inbox)
    {
        $validated = $request->validate([
            'peer_phone' => 'required|string|max:32',
            'body' => 'required|string|max:4096',
            'whatsapp_connection_id' => 'nullable|integer|min:1',
        ]);

        $to = PhoneNumber::toWhatsAppDigits($validated['peer_phone']);
        abort_unless(PhoneNumber::isValidWhatsAppDigits($to), 422);

        $connectionId = (int) ($validated['whatsapp_connection_id'] ?? 0);
        if ($connectionId <= 0) {
            $connectionId = (int) (WhatsAppConnection::query()->active()->defaultNumber()->orderBy('id')->value('id') ?? 0);
        }
        abort_unless($connectionId > 0, 422, 'No default WhatsApp number configured.');

        $connection = WhatsAppConnection::query()->findOrFail($connectionId);
        abort_unless((bool) $connection->is_default, 422, 'Only default numbers can be used here.');

        $prepared = $connections->prepareConnectionById($connectionId);
        if (! ($prepared['ready'] ?? false)) {
            return response()->json(['ok' => false, 'message' => $prepared['message'] ?? 'Not ready'], 422);
        }

        $payload = $inbox->defaultThreadPayload((string) $to);
        if (! ($payload['open_window'] ?? false)) {
            return response()->json(['ok' => false, 'message' => '24-hour window is closed.'], 422);
        }

        SendWhatsAppComposeJob::dispatch(
            0,
            0,
            $connectionId,
            $to,
            'text',
            (string) $validated['body'],
            null,
            'en',
            [],
            true
        )->onQueue('notifications');

        return response()->json(['ok' => true]);
    }

    public function resolveConflict(Request $request)
    {
        $validated = $request->validate([
            'message_id' => 'required|integer|min:1',
            'participant_type' => 'required|string|max:64',
            'participant_id' => 'required|integer|min:1',
        ]);

        $message = WhatsAppMessage::withoutGlobalScope('company')
            ->whereKey((int) $validated['message_id'])
            ->firstOrFail();

        $conflict = $message->participant_conflict;
        $candidates = is_array($conflict['candidates'] ?? null) ? $conflict['candidates'] : [];
        $ok = collect($candidates)->contains(
            fn ($c) => ($c['type'] ?? '') === $validated['participant_type']
                && (int) ($c['id'] ?? 0) === (int) $validated['participant_id']
        );
        abort_unless($ok, 422);

        $message->participant_type = $validated['participant_type'];
        $message->participant_id = (int) $validated['participant_id'];
        $message->participant_conflict = null;
        $message->save();

        return response()->json(['ok' => true]);
    }
}
