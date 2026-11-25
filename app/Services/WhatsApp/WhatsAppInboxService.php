<?php

namespace App\Services\WhatsApp;

use App\Models\User;
use App\Models\WhatsAppMessage;
use App\Support\PhoneNumber;
use App\Support\WhatsApp\WhatsAppRecipientTypes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class WhatsAppInboxService
{
    public function __construct(
        private WhatsAppMessageStore $store,
        private WhatsAppConnectionService $connections
    ) {
    }

    public function isCompanyAdmin(User $user): bool
    {
        return method_exists($user, 'isAdmin') && $user->isAdmin();
    }

    /**
     * Stable chat-thread id: phone + participant (unknown when unset).
     */
    public function threadKey(string $peerPhone, ?string $participantType, ?int $participantId): string
    {
        $peer = PhoneNumber::toWhatsAppDigits($peerPhone) ?? $peerPhone;
        if ($participantType && $participantId) {
            return $peer.'|'.$participantType.'|'.$participantId;
        }

        return $peer.'|unknown';
    }

    /**
     * Scope a message query to one participant thread on a phone.
     */
    public function applyParticipantScope(Builder $query, ?string $participantType, ?int $participantId): Builder
    {
        if ($participantType && $participantId) {
            return $query
                ->where('participant_type', $participantType)
                ->where('participant_id', $participantId);
        }

        return $query->whereNull('participant_type')->whereNull('participant_id');
    }

    /**
     * @return list<array{key: string, label: string, count: int}>
     */
    public function typeFilters(User $user, int $companyId): array
    {
        // Collapse to one row per thread in SQL, then count by morph in PHP.
        $threadMorphs = $this->visiblePeersQuery($user, $companyId)
            ->select('participant_type')
            ->groupBy('peer_phone', 'participant_type', 'participant_id')
            ->pluck('participant_type');

        $countsByType = [];
        foreach ($threadMorphs as $morph) {
            $key = WhatsAppRecipientTypes::keyForMorph($morph) ?? WhatsAppRecipientTypes::UNKNOWN;
            $countsByType[$key] = ($countsByType[$key] ?? 0) + 1;
        }

        $total = array_sum($countsByType);
        $filters = [
            [
                'key' => 'all',
                'label' => 'All',
                'count' => $total,
            ],
        ];
        foreach (WhatsAppRecipientTypes::filterKeys() as $key) {
            $count = (int) ($countsByType[$key] ?? 0);
            if ($count > 0) {
                $filters[] = [
                    'key' => $key,
                    'label' => WhatsAppRecipientTypes::labelFor($key),
                    'count' => $count,
                ];
            }
        }

        return $filters;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listConversations(
        User $user,
        int $companyId,
        ?string $typeKey = null,
        ?string $nameSearch = null,
        ?int $participantId = null
    ): array {
        $query = $this->visiblePeersQuery($user, $companyId);
        $this->applyConversationFilters($query, $companyId, $typeKey, $nameSearch, $participantId);

        // One tip id per thread (avoids N+1 latest lookups).
        $tipIds = (clone $query)
            ->selectRaw('MAX(id) as tip_id')
            ->groupBy('peer_phone', 'participant_type', 'participant_id')
            ->pluck('tip_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        if ($tipIds === []) {
            return [];
        }

        $latests = WhatsAppMessage::query()
            ->whereIn('id', $tipIds)
            ->with(['participant'])
            ->get([
                'id', 'peer_phone', 'participant_type', 'participant_id', 'participant_conflict',
                'body', 'message_type', 'is_system', 'occurred_at',
            ])
            ->keyBy('id');

        $unreadByKey = $this->unreadCountsByThread($user, $companyId, $typeKey, $nameSearch, $participantId);

        $rows = [];
        foreach ($tipIds as $tipId) {
            /** @var WhatsAppMessage|null $latest */
            $latest = $latests->get($tipId);
            if (! $latest) {
                continue;
            }

            $peer = (string) $latest->peer_phone;
            $pType = $latest->participant_type;
            $pId = $latest->participant_id ? (int) $latest->participant_id : null;
            $key = $this->threadKey($peer, $pType, $pId);

            [$label, $trashed] = $this->formatPeerHeader($latest, $peer, false);
            $participant = $latest->participant;

            $rows[] = [
                'thread_key' => $key,
                'peer_phone' => $peer,
                'label' => $label,
                'trashed' => $trashed,
                'photo' => WhatsAppRecipientTypes::photoUrl($participant),
                'last_body' => $latest->is_system
                    ? (string) $latest->body
                    : $this->previewBody($latest),
                'last_at' => optional($latest->occurred_at)?->toIso8601String(),
                'participant_type' => $pType,
                'participant_id' => $pId,
                'has_conflict' => ! empty($latest->participant_conflict),
                'unread_count' => (int) ($unreadByKey[$key] ?? 0),
            ];
        }

        usort($rows, function ($a, $b) {
            return strcmp((string) ($b['last_at'] ?? ''), (string) ($a['last_at'] ?? ''));
        });

        return $rows;
    }

    /**
     * @return array<string, int> thread_key => unread inbound count
     */
    private function unreadCountsByThread(
        User $user,
        int $companyId,
        ?string $typeKey = null,
        ?string $nameSearch = null,
        ?int $participantId = null
    ): array {
        $query = $this->visiblePeersQuery($user, $companyId)
            ->where('direction', WhatsAppMessage::DIRECTION_IN)
            ->where('status', '!=', WhatsAppMessage::STATUS_READ);
        $this->applyConversationFilters($query, $companyId, $typeKey, $nameSearch, $participantId);

        $rows = $query
            ->selectRaw('peer_phone, participant_type, participant_id, COUNT(*) as unread_count')
            ->groupBy('peer_phone', 'participant_type', 'participant_id')
            ->get();

        $map = [];
        foreach ($rows as $row) {
            $key = $this->threadKey(
                (string) $row->peer_phone,
                $row->participant_type,
                $row->participant_id ? (int) $row->participant_id : null
            );
            $map[$key] = (int) $row->unread_count;
        }

        return $map;
    }

    private function applyConversationFilters(
        Builder $query,
        int $companyId,
        ?string $typeKey,
        ?string $nameSearch,
        ?int $participantId
    ): void {
        $typeKey = ($typeKey === 'all') ? null : $typeKey;

        if ($typeKey && $typeKey !== '') {
            if ($typeKey === WhatsAppRecipientTypes::UNKNOWN) {
                $query->whereNull('participant_type');
            } else {
                $morph = WhatsAppRecipientTypes::morphAliasForKey($typeKey);
                if ($morph) {
                    $query->where('participant_type', $morph);
                }
            }
        }

        if ($participantId && $typeKey && $typeKey !== WhatsAppRecipientTypes::UNKNOWN) {
            $morph = WhatsAppRecipientTypes::morphAliasForKey($typeKey);
            if ($morph) {
                $query->where('participant_type', $morph)->where('participant_id', $participantId);
            }
        }

        $term = is_string($nameSearch) ? trim($nameSearch) : '';
        if ($term === '') {
            return;
        }

        // Concrete type: match participant name fields for that morph.
        if ($typeKey && $typeKey !== WhatsAppRecipientTypes::UNKNOWN) {
            $matchedIds = $this->searchParticipantIds($typeKey, $companyId, $term);
            if ($matchedIds === []) {
                $query->whereRaw('0 = 1');

                return;
            }
            $morph = WhatsAppRecipientTypes::morphAliasForKey($typeKey);
            $query->where('participant_type', $morph)->whereIn('participant_id', $matchedIds);

            return;
        }

        // All / unknown: match peer phone and/or any known participant by name.
        $phoneTerm = preg_replace('/\D+/', '', $term) ?: $term;
        $matchedByMorph = [];
        if (! $typeKey) {
            foreach (WhatsAppRecipientTypes::filterKeys() as $key) {
                if ($key === WhatsAppRecipientTypes::UNKNOWN) {
                    continue;
                }
                $ids = $this->searchParticipantIds($key, $companyId, $term);
                $morph = WhatsAppRecipientTypes::morphAliasForKey($key);
                if ($morph && $ids !== []) {
                    $matchedByMorph[$morph] = $ids;
                }
            }
        }

        $query->where(function (Builder $outer) use ($term, $phoneTerm, $matchedByMorph) {
            $outer->where('peer_phone', 'like', '%'.$phoneTerm.'%');
            foreach ($matchedByMorph as $morph => $ids) {
                $outer->orWhere(function (Builder $inner) use ($morph, $ids) {
                    $inner->where('participant_type', $morph)->whereIn('participant_id', $ids);
                });
            }
        });
    }

    /**
     * @return array{items: list<array<string, mixed>>, open_window: bool, can_send: bool, send_from: list<array>, conflict: ?array, peer_label: string, trashed: bool}
     */
    public function threadPayload(
        User $user,
        int $companyId,
        string $peerPhone,
        ?int $afterId = null,
        ?string $participantType = null,
        ?int $participantId = null
    ): array {
        $peerPhone = PhoneNumber::toWhatsAppDigits($peerPhone) ?? $peerPhone;
        $participantType = $participantType ?: null;
        $participantId = $participantId ?: null;

        $messagesQuery = WhatsAppMessage::query()
            ->where('company_id', $companyId)
            ->where('peer_phone', $peerPhone)
            ->where('user_id', $user->id)
            // Incremental polls only need owner names for gap lines; skip morph/connection.
            ->with($afterId
                ? ['user:id,name']
                : ['user:id,name', 'participant', 'connection'])
            ->when($afterId, fn ($q) => $q->where('id', '>', $afterId));
        $this->applyParticipantScope($messagesQuery, $participantType, $participantId);

        $messages = $messagesQuery
            ->orderBy('occurred_at')
            ->orderBy('id')
            ->get();

        // Opening the thread counts as reading inbound messages.
        if ($afterId === null) {
            $this->markInboundRead($user, $companyId, $peerPhone, $participantType, $participantId);
            $messages->each(function (WhatsAppMessage $m) {
                if ($m->direction === WhatsAppMessage::DIRECTION_IN
                    && $m->status !== WhatsAppMessage::STATUS_READ) {
                    $m->status = WhatsAppMessage::STATUS_READ;
                    $m->status_at = now();
                }
            });
        }

        $items = $this->buildGapAwareTimeline($messages, (int) $user->id);

        $latest = $messages->last();
        $conflict = null;
        foreach ($messages->reverse() as $m) {
            if (! empty($m->participant_conflict)) {
                $conflict = $m->participant_conflict;
                $conflict['message_id'] = $m->id;
                break;
            }
        }

        $sendFrom = $this->connections->getConnectionsForUserSend($user, $companyId)
            ->map(fn ($c) => [
                'id' => $c->id,
                'label' => method_exists($c, 'displayLabel') ? $c->displayLabel() : ($c->label ?: $c->display_phone_number),
            ])->values()->all();

        $connectionIds = array_map(fn ($c) => (int) $c['id'], $sendFrom);
        $openByConnection = $this->openWindowsByConnection($companyId, $peerPhone, $connectionIds);

        // Full opens resolve peer meta; incremental polls keep the client header as-is.
        $peerLabel = '';
        $trashed = false;
        $photo = null;
        if (! $afterId) {
            [$peerLabel, $trashed] = $this->formatPeerHeader($latest, $peerPhone);
            $photo = $this->participantPhoto($latest, $participantType, $participantId);
        } elseif ($latest) {
            [$peerLabel, $trashed] = $this->formatPeerHeader($latest, $peerPhone);
            $photo = $this->participantPhoto($latest, $participantType, $participantId);
        }

        return [
            'items' => $items,
            'open_window_by_connection' => $openByConnection,
            'can_send' => $sendFrom !== [] && $user->can('whatsapp_create'),
            'send_from' => $sendFrom,
            'conflict' => $conflict,
            'peer_label' => $peerLabel,
            'trashed' => $trashed,
            'photo' => $photo,
            'peer_phone' => $peerPhone,
            'participant_type' => $participantType,
            'participant_id' => $participantId,
            'thread_key' => $this->threadKey($peerPhone, $participantType, $participantId),
        ];
    }

    private function participantPhoto(
        ?WhatsAppMessage $latest,
        ?string $participantType = null,
        ?int $participantId = null
    ): ?string {
        $participant = $latest?->participant;
        if ($participant) {
            return WhatsAppRecipientTypes::photoUrl($participant);
        }

        $type = $participantType ?: $latest?->participant_type;
        $id = $participantId ?: ($latest?->participant_id ? (int) $latest->participant_id : null);
        if (! $type || ! $id) {
            return null;
        }

        $key = WhatsAppRecipientTypes::keyForMorph($type);
        if (! $key) {
            return null;
        }
        $query = WhatsAppRecipientTypes::baseQuery($key);
        if (! $query) {
            return null;
        }

        return WhatsAppRecipientTypes::photoUrl($query->withoutGlobalScope('company')->whereKey($id)->first());
    }

    /**
     * Known: name (sidebar) or "Name · +phone" (chat header); unknown: +phone.
     *
     * @return array{0: string, 1: bool}
     */
    private function formatPeerHeader(?WhatsAppMessage $latest, string $peerPhone, bool $includePhone = true): array
    {
        $displayPhone = $this->formatPeerPhone($peerPhone);
        $trashed = false;

        $participant = $latest?->participant;
        if (! $participant && $latest?->participant_type && $latest?->participant_id) {
            $latest->loadMissing('participant');
            $participant = $latest->participant;
        }

        if ($participant) {
            $name = trim((string) WhatsAppRecipientTypes::displayName($participant));
            $trashed = WhatsAppRecipientTypes::isTrashed($participant);
            if ($name === '') {
                $label = $displayPhone;
            } elseif ($includePhone) {
                $label = $name.' · '.$displayPhone;
            } else {
                $label = $name;
            }
            if ($trashed) {
                $label .= ' (moved to trash)';
            }

            return [$label, $trashed];
        }

        return [$displayPhone, false];
    }

    private function formatPeerPhone(string $peerPhone): string
    {
        $digits = PhoneNumber::toWhatsAppDigits($peerPhone) ?? $peerPhone;

        return $digits !== '' ? '+'.$digits : $peerPhone;
    }

    /**
     * Mark inbound messages in this thread as read for the viewing user.
     */
    public function markInboundRead(
        User $user,
        ?int $companyId,
        string $peerPhone,
        ?string $participantType = null,
        ?int $participantId = null
    ): int {
        $peerPhone = PhoneNumber::toWhatsAppDigits($peerPhone) ?? $peerPhone;

        $query = WhatsAppMessage::query()
            ->when(
                $companyId === null,
                fn ($q) => $q->withoutGlobalScope('company')->whereNull('company_id'),
                fn ($q) => $q->where('company_id', $companyId)
            )
            ->where('peer_phone', $peerPhone)
            ->where('direction', WhatsAppMessage::DIRECTION_IN)
            ->where('status', '!=', WhatsAppMessage::STATUS_READ);
        $this->applyParticipantScope($query, $participantType, $participantId);

        if ($companyId !== null) {
            $query->where('user_id', $user->id);
        }

        return $query->update([
            'status' => WhatsAppMessage::STATUS_READ,
            'status_at' => now(),
        ]);
    }

    public function hasOpenWindow(int $companyId, string $peerPhone, int $connectionId): bool
    {
        $map = $this->openWindowsByConnection($companyId, $peerPhone, [$connectionId]);

        return (bool) ($map[$connectionId] ?? false);
    }

    /**
     * Batch 24h inbound-window checks (one query for all send-from connections).
     *
     * @param  list<int>  $connectionIds
     * @return array<int, bool> connection_id => open
     */
    public function openWindowsByConnection(int $companyId, string $peerPhone, array $connectionIds): array
    {
        $peerPhone = PhoneNumber::toWhatsAppDigits($peerPhone) ?? $peerPhone;
        $connectionIds = array_values(array_unique(array_filter(
            array_map('intval', $connectionIds),
            fn (int $id) => $id > 0
        )));

        $open = array_fill_keys($connectionIds, false);
        if ($connectionIds === [] || $peerPhone === '') {
            return $open;
        }

        $openIds = WhatsAppMessage::query()
            ->where('company_id', $companyId)
            ->where('peer_phone', $peerPhone)
            ->whereIn('whatsapp_connection_id', $connectionIds)
            ->where('direction', WhatsAppMessage::DIRECTION_IN)
            ->where('occurred_at', '>=', now()->subHours(24))
            ->distinct()
            ->pluck('whatsapp_connection_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        foreach ($openIds as $id) {
            $open[$id] = true;
        }

        return $open;
    }

    /**
     * Batch 24h inbound-window checks for many peers on one sender connection.
     *
     * @param  list<string>  $peerPhones
     * @return array<string, bool> normalized peer_phone => open
     */
    public function openWindowsForPeers(int $companyId, int $connectionId, array $peerPhones): array
    {
        $normalized = [];
        foreach ($peerPhones as $phone) {
            $digits = PhoneNumber::toWhatsAppDigits(is_string($phone) ? $phone : null);
            if (PhoneNumber::isValidWhatsAppDigits($digits)) {
                $normalized[$digits] = false;
            }
        }

        if ($normalized === [] || $connectionId <= 0 || $companyId <= 0) {
            return $normalized;
        }

        $openPhones = WhatsAppMessage::query()
            ->where('company_id', $companyId)
            ->where('whatsapp_connection_id', $connectionId)
            ->whereIn('peer_phone', array_keys($normalized))
            ->where('direction', WhatsAppMessage::DIRECTION_IN)
            ->where('occurred_at', '>=', now()->subHours(24))
            ->distinct()
            ->pluck('peer_phone')
            ->map(fn ($phone) => (string) $phone)
            ->all();

        foreach ($openPhones as $phone) {
            if (array_key_exists($phone, $normalized)) {
                $normalized[$phone] = true;
            }
        }

        return $normalized;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function buildGapAwareTimeline(Collection $messages, int $viewerId): array
    {
        $items = [];
        $gapStart = null;
        $gapUserId = null;
        $gapUserName = null;
        $replyParents = $this->loadReplyParents($messages);

        $flushGap = function () use (&$items, &$gapStart, &$gapUserId, &$gapUserName) {
            if ($gapStart && $gapUserId) {
                $items[] = [
                    'type' => 'gap',
                    'user_id' => $gapUserId,
                    'user_name' => $gapUserName ?: ('User #'.$gapUserId),
                    'from' => $gapStart['from'],
                    'to' => $gapStart['to'],
                ];
            }
            $gapStart = null;
            $gapUserId = null;
            $gapUserName = null;
        };

        foreach ($messages as $message) {
            $ownerId = $message->user_id ? (int) $message->user_id : null;

            if ($ownerId === $viewerId) {
                $flushGap();
                $items[] = $this->serializeBubble($message, false, $replyParents);
                continue;
            }

            // Other user's messages → collapse into gap line (skip null/admin-only).
            if ($ownerId === null) {
                continue;
            }

            $name = $message->user?->name ?: ('User #'.$ownerId);
            $at = optional($message->occurred_at)->toDateString()
                ?: optional($message->created_at)->toDateString()
                ?: now()->toDateString();

            if ($gapUserId === $ownerId && $gapStart) {
                $gapStart['to'] = $at;
            } else {
                $flushGap();
                $gapUserId = $ownerId;
                $gapUserName = $name;
                $gapStart = ['from' => $at, 'to' => $at];
            }
        }
        $flushGap();

        return $items;
    }

    /**
     * @return array<string, mixed>
     */
    private function previewBody(WhatsAppMessage $message): string
    {
        if ($message->body) {
            return (string) $message->body;
        }

        $type = (string) ($message->message_type ?: '');
        if (in_array($type, WhatsAppMediaService::TYPES, true)) {
            if ($type === 'document' && $message->media_filename) {
                return 'Document: '.$message->media_filename;
            }

            return ucfirst($type);
        }

        return $type !== '' ? $type : '';
    }

    /**
     * Meta puts reply/quote context on inbound messages as:
     * payload.context.id (wamid of the quoted message) and payload.context.from.
     *
     * @param  Collection<int, WhatsAppMessage>  $messages
     * @return array<string, WhatsAppMessage> keyed by wa_message_id
     */
    private function loadReplyParents(Collection $messages): array
    {
        $ids = [];
        foreach ($messages as $message) {
            $id = $this->replyToWaId($message);
            if ($id !== null) {
                $ids[$id] = true;
            }
        }

        if ($ids === []) {
            return [];
        }

        return WhatsAppMessage::withoutGlobalScope('company')
            ->whereIn('wa_message_id', array_keys($ids))
            ->orderByDesc('id')
            ->get()
            ->unique('wa_message_id')
            ->keyBy(fn (WhatsAppMessage $m) => (string) $m->wa_message_id)
            ->all();
    }

    private function replyToWaId(WhatsAppMessage $message): ?string
    {
        $payload = is_array($message->payload) ? $message->payload : [];
        $id = data_get($payload, 'context.id')
            ?? data_get($payload, 'context.message_id');
        $id = is_string($id) ? trim($id) : '';

        return $id !== '' ? $id : null;
    }

    /**
     * @param  array<string, WhatsAppMessage>  $replyParents
     * @return array{id: ?int, wa_message_id: string, direction: ?string, message_type: ?string, body: ?string}|null
     */
    private function serializeReplyTo(WhatsAppMessage $message, array $replyParents): ?array
    {
        $replyWaId = $this->replyToWaId($message);
        if ($replyWaId === null) {
            return null;
        }

        $parent = $replyParents[$replyWaId] ?? null;
        if ($parent instanceof WhatsAppMessage) {
            $preview = $this->previewBody($parent);
            if ($preview === '' && in_array((string) $parent->message_type, WhatsAppMediaService::TYPES, true)) {
                $preview = ucfirst((string) $parent->message_type);
            }

            return [
                'id' => (int) $parent->id,
                'wa_message_id' => $replyWaId,
                'direction' => $parent->direction,
                'message_type' => $parent->message_type,
                'body' => $preview !== '' ? $preview : 'Original message',
            ];
        }

        return [
            'id' => null,
            'wa_message_id' => $replyWaId,
            'direction' => null,
            'message_type' => null,
            'body' => 'Original message',
        ];
    }

    /**
     * @param  array<string, WhatsAppMessage>  $replyParents
     */
    private function serializeBubble(WhatsAppMessage $message, bool $showOwner, array $replyParents = []): array
    {
        $type = (string) ($message->message_type ?: 'message');
        $body = $message->body;
        if ($body === null || $body === '') {
            // Media bubbles use message_type + media payload; keep body as caption only
            // so the UI does not show leftover "Image"/"Audio" labels after download.
            if (in_array($type, WhatsAppMediaService::TYPES, true)) {
                $body = $type === 'document' ? ($message->media_filename ?: null) : null;
            } elseif ($type === 'sticker') {
                $body = 'Sticker';
            } else {
                $body = $message->template_name ?: $type;
            }
        }

        $occurredAt = $message->occurred_at ?? $message->created_at;
        $media = in_array($type, WhatsAppMediaService::TYPES, true)
            ? app(WhatsAppMediaService::class)->serializeMedia($message)
            : null;

        return [
            'type' => 'message',
            'id' => $message->id,
            'direction' => $message->direction,
            'message_type' => $type,
            'body' => $body,
            'media' => $media,
            'reply_to' => $this->serializeReplyTo($message, $replyParents),
            'status' => $message->status,
            'is_system' => (bool) $message->is_system,
            // Absolute instant (ISO) — browser formats in the reader's local timezone.
            'occurred_at' => optional($occurredAt)?->toIso8601String(),
            'user_id' => $message->user_id,
            'user_name' => $showOwner ? ($message->user?->name) : null,
            'connection_label' => $message->connection
                ? (method_exists($message->connection, 'displayLabel')
                    ? $message->connection->displayLabel()
                    : $message->connection->label)
                : null,
        ];
    }

    private function visiblePeersQuery(User $user, int $companyId): Builder
    {
        return WhatsAppMessage::query()
            ->where('company_id', $companyId)
            ->where('user_id', $user->id);
    }

    /**
     * @return list<int>
     */
    private function searchParticipantIds(string $typeKey, int $companyId, string $term): array
    {
        $query = WhatsAppRecipientTypes::baseQuery($typeKey);
        if (! $query) {
            return [];
        }

        if (\Illuminate\Support\Facades\Schema::hasColumn($query->getModel()->getTable(), 'company_id')) {
            $query->where('company_id', $companyId);
        }

        $like = '%'.$term.'%';
        $query->where(function ($q) use ($like) {
            $q->where('name', 'like', $like);
            if (\Illuminate\Support\Facades\Schema::hasColumn($q->getModel()->getTable(), 'company_name')) {
                $q->orWhere('company_name', 'like', $like);
            }
            if (\Illuminate\Support\Facades\Schema::hasColumn($q->getModel()->getTable(), 'email')) {
                $q->orWhere('email', 'like', $like);
            }
        });

        return $query->limit(50)->pluck($query->getModel()->getKeyName())->map(fn ($id) => (int) $id)->all();
    }

    public function resolveConflict(User $user, int $companyId, int $messageId, string $morphType, int $participantId): WhatsAppMessage
    {
        $message = WhatsAppMessage::query()
            ->where('company_id', $companyId)
            ->whereKey($messageId)
            ->firstOrFail();

        $conflict = $message->participant_conflict;
        $candidates = is_array($conflict['candidates'] ?? null) ? $conflict['candidates'] : [];
        $ok = collect($candidates)->contains(fn ($c) => ($c['type'] ?? '') === $morphType && (int) ($c['id'] ?? 0) === $participantId);
        abort_unless($ok || $this->isCompanyAdmin($user), 422, 'Invalid participant choice.');

        $message->participant_type = $morphType;
        $message->participant_id = $participantId;
        $message->participant_conflict = null;
        $message->save();

        return $message;
    }

    /**
     * @param  'full'|'from_now'  $historyMode
     */
    public function transfer(
        User $actor,
        int $companyId,
        string $peerPhone,
        int $toUserId,
        string $reason,
        string $historyMode,
        ?int $connectionId = null,
        ?string $participantType = null,
        ?int $participantId = null
    ): WhatsAppMessage {
        $peerPhone = PhoneNumber::toWhatsAppDigits($peerPhone) ?? $peerPhone;
        $target = User::query()->whereKey($toUserId)->firstOrFail();
        abort_unless(
            (int) ($target->company_id ?? 0) === $companyId,
            422,
            'Target user is not in this company.'
        );
        if (\Illuminate\Support\Facades\Schema::hasColumn('users', 'status')) {
            abort_unless((bool) $target->status, 422, 'Target user is inactive.');
        }

        $isUnknownAssignee = \App\Models\WhatsAppUnknownInboxSetting::query()
            ->where('company_id', $companyId)
            ->where('user_id', $toUserId)
            ->exists();
        abort_unless(
            $isUnknownAssignee
                || $target->can('whatsapp_view')
                || $this->isCompanyAdmin($target),
            422,
            'Target user cannot receive WhatsApp chats.'
        );

        $latestQuery = WhatsAppMessage::query()
            ->where('company_id', $companyId)
            ->where('peer_phone', $peerPhone)
            ->where('user_id', $actor->id)
            ->when($connectionId, fn ($q) => $q->where('whatsapp_connection_id', $connectionId));
        $this->applyParticipantScope($latestQuery, $participantType, $participantId);
        $latest = $latestQuery
            ->orderByDesc('occurred_at')
            ->orderByDesc('id')
            ->first();

        abort_unless($latest, 403);

        $pType = $participantType ?: $latest?->participant_type;
        $pId = $participantId ?: ($latest?->participant_id ? (int) $latest->participant_id : null);

        return DB::transaction(function () use ($actor, $companyId, $peerPhone, $toUserId, $reason, $historyMode, $connectionId, $latest, $pType, $pId) {
            if ($historyMode === 'full') {
                $q = WhatsAppMessage::query()
                    ->where('company_id', $companyId)
                    ->where('peer_phone', $peerPhone)
                    ->where('user_id', $actor->id);
                $this->applyParticipantScope($q, $pType, $pId);
                if ($connectionId) {
                    $q->where('whatsapp_connection_id', $connectionId);
                }
                $q->update(['user_id' => $toUserId]);
            }

            $connId = $connectionId ?: ($latest?->whatsapp_connection_id);
            $body = sprintf(
                'Chat transferred by %s to user #%d. Reason: %s (%s)',
                $actor->name ?: ('User #'.$actor->id),
                $toUserId,
                $reason,
                $historyMode === 'full' ? 'full history' : 'from now'
            );

            return $this->store->recordSystem([
                'company_id' => $companyId,
                'whatsapp_connection_id' => $connId,
                'peer_phone' => $peerPhone,
                'body' => $body,
                'user_id' => $toUserId,
                'participant_type' => $pType,
                'participant_id' => $pId,
                'source' => WhatsAppMessage::SOURCE_CHAT_TRANSFER,
                'status' => WhatsAppMessage::STATUS_SENT,
            ]);
        });
    }

    public function deleteUserChat(
        User $user,
        int $companyId,
        string $peerPhone,
        ?string $participantType = null,
        ?int $participantId = null
    ): int {
        $peerPhone = PhoneNumber::toWhatsAppDigits($peerPhone) ?? $peerPhone;

        $idsQuery = WhatsAppMessage::query()
            ->where('company_id', $companyId)
            ->where('peer_phone', $peerPhone)
            ->where('user_id', $user->id);
        $this->applyParticipantScope($idsQuery, $participantType, $participantId);
        $ids = $idsQuery
            ->orderByDesc('occurred_at')
            ->orderByDesc('id')
            ->pluck('id');

        if ($ids->isEmpty()) {
            return 0;
        }

        $keepId = (int) $ids->first();
        $deleteIds = $ids->filter(fn ($id) => (int) $id !== $keepId)->values()->all();
        if ($deleteIds === []) {
            return 0;
        }

        return WhatsAppMessage::query()->whereIn('id', $deleteIds)->delete();
    }

    /**
     * Admin default-number scope: company_id null + default connections.
     *
     * @return list<array<string, mixed>>
     */
    public function listDefaultConversations(?string $search = null): array
    {
        $defaultIds = \App\Models\WhatsAppConnection::query()
            ->defaultNumber()
            ->pluck('id')
            ->all();

        if ($defaultIds === []) {
            return [];
        }

        $q = WhatsAppMessage::withoutGlobalScope('company')
            ->whereNull('company_id')
            ->whereIn('whatsapp_connection_id', $defaultIds);

        if ($search) {
            $q->where('peer_phone', 'like', '%'.preg_replace('/\D+/', '', $search).'%');
        }

        $peers = $q->pluck('peer_phone')->unique()->values();
        $rows = [];
        foreach ($peers as $peer) {
            $latest = WhatsAppMessage::withoutGlobalScope('company')
                ->whereNull('company_id')
                ->where('peer_phone', $peer)
                ->orderByDesc('occurred_at')
                ->orderByDesc('id')
                ->first();
            if (! $latest) {
                continue;
            }
            $rows[] = [
                'peer_phone' => $peer,
                'label' => $peer,
                'last_body' => $this->previewBody($latest),
                'last_at' => optional($latest->occurred_at)?->toIso8601String(),
                'has_conflict' => ! empty($latest->participant_conflict),
            ];
        }

        usort($rows, fn ($a, $b) => strcmp((string) ($b['last_at'] ?? ''), (string) ($a['last_at'] ?? '')));

        return $rows;
    }

    /**
     * @return array{items: list<array<string, mixed>>, conflict: ?array, open_window: bool, peer_phone: string}
     */
    public function defaultThreadPayload(string $peerPhone, ?int $afterId = null): array
    {
        $peerPhone = PhoneNumber::toWhatsAppDigits($peerPhone) ?? $peerPhone;
        $defaultIds = \App\Models\WhatsAppConnection::query()->defaultNumber()->pluck('id')->all();

        $messages = WhatsAppMessage::withoutGlobalScope('company')
            ->whereNull('company_id')
            ->where('peer_phone', $peerPhone)
            ->with(['participant'])
            ->when($defaultIds, fn ($q) => $q->whereIn('whatsapp_connection_id', $defaultIds))
            ->when($afterId, fn ($q) => $q->where('id', '>', $afterId))
            ->orderBy('occurred_at')
            ->orderBy('id')
            ->get();

        if ($afterId === null) {
            WhatsAppMessage::withoutGlobalScope('company')
                ->whereNull('company_id')
                ->where('peer_phone', $peerPhone)
                ->where('direction', WhatsAppMessage::DIRECTION_IN)
                ->where('status', '!=', WhatsAppMessage::STATUS_READ)
                ->update([
                    'status' => WhatsAppMessage::STATUS_READ,
                    'status_at' => now(),
                ]);
            $messages->each(function (WhatsAppMessage $m) {
                if ($m->direction === WhatsAppMessage::DIRECTION_IN
                    && $m->status !== WhatsAppMessage::STATUS_READ) {
                    $m->status = WhatsAppMessage::STATUS_READ;
                    $m->status_at = now();
                }
            });
        }

        $conflict = null;
        foreach ($messages->reverse() as $m) {
            if (! empty($m->participant_conflict)) {
                $conflict = $m->participant_conflict;
                $conflict['message_id'] = $m->id;
                break;
            }
        }

        $defaultConn = \App\Models\WhatsAppConnection::query()->active()->defaultNumber()->orderBy('id')->first();
        $open = false;
        if ($defaultConn) {
            $open = WhatsAppMessage::withoutGlobalScope('company')
                ->whereNull('company_id')
                ->where('peer_phone', $peerPhone)
                ->where('whatsapp_connection_id', $defaultConn->id)
                ->where('direction', WhatsAppMessage::DIRECTION_IN)
                ->where('occurred_at', '>=', now()->subHours(24))
                ->exists();
        }

        $latest = $messages->last();
        [$peerLabel] = $this->formatPeerHeader($latest, $peerPhone);

        $replyParents = $this->loadReplyParents($messages);

        return [
            'items' => $messages->map(fn ($m) => $this->serializeBubble($m, true, $replyParents))->all(),
            'conflict' => $conflict,
            'open_window' => $open,
            'peer_phone' => $peerPhone,
            'peer_label' => $peerLabel,
            'connection_id' => $defaultConn?->id,
        ];
    }
}
