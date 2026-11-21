<?php

namespace App\Services\WhatsApp;

use App\Models\User;
use App\Models\WhatsAppMessage;
use App\Models\WhatsAppUnknownInboxSetting;
use App\Support\PhoneNumber;
use App\Support\WhatsApp\WhatsAppRecipientTypes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class WhatsAppParticipantResolver
{
    /**
     * @return array{
     *   participant: ?Model,
     *   participant_type: ?string,
     *   participant_id: ?int,
     *   user_id: ?int,
     *   participant_conflict: ?array
     * }
     */
    public function resolve(
        ?int $companyId,
        string $peerPhone,
        ?int $whatsappConnectionId = null
    ): array {
        $peerPhone = PhoneNumber::toWhatsAppDigits($peerPhone) ?? $peerPhone;

        $result = [
            'participant' => null,
            'participant_type' => null,
            'participant_id' => null,
            'user_id' => null,
            'participant_conflict' => null,
        ];

        if ($peerPhone === '') {
            return $result;
        }

        $latest = $this->latestMessage($companyId, $peerPhone, $whatsappConnectionId);
        // Tip may be an outbound compose row without participant — prefer any
        // earlier thread row that still has a morph link.
        $latestWithParticipant = $this->latestMessageWithParticipant($companyId, $peerPhone, $whatsappConnectionId);

        if ($latest || $latestWithParticipant) {
            // Prefer last owned message — do not inherit a poisoned null user_id
            // from an unowned latest row.
            $result['user_id'] = $this->continuityUserId($companyId, $peerPhone, $whatsappConnectionId);

            $participantSource = ($latest && $latest->participant_type && $latest->participant_id)
                ? $latest
                : $latestWithParticipant;

            if ($participantSource && $participantSource->participant_type && $participantSource->participant_id) {
                $kept = $this->resolveFromLatestParticipant($participantSource, $peerPhone, $companyId);
                $result = array_merge($result, $kept);
            } elseif ($latest && ! empty($latest->participant_conflict)) {
                $result['participant_conflict'] = $latest->participant_conflict;
            } else {
                $lookup = $this->registryLookup($peerPhone, $companyId);
                $result = array_merge($result, $lookup);
            }
        } else {
            // First inbound for this peer (no prior thread): keep any known
            // participant match, but always route ownership to the number's
            // unknown-inbound assignee when configured (known or unknown).
            $lookup = $this->registryLookup($peerPhone, $companyId);
            $result = array_merge($result, $lookup);

            if ($companyId && $whatsappConnectionId && ! $result['user_id']) {
                $assignee = $this->unknownAssignee((int) $companyId, (int) $whatsappConnectionId);
                if ($assignee) {
                    $result['user_id'] = $assignee;
                }
            }
        }

        $result['user_id'] = $this->sanitizeUserId($result['user_id'], $companyId, $whatsappConnectionId);

        return $result;
    }

    private function latestMessage(?int $companyId, string $peerPhone, ?int $connectionId): ?WhatsAppMessage
    {
        $base = WhatsAppMessage::withoutGlobalScope('company')
            ->where('peer_phone', $peerPhone)
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->when(! $companyId, fn ($q) => $q->whereNull('company_id'));

        if ($connectionId) {
            $sameConn = (clone $base)
                ->where('whatsapp_connection_id', $connectionId)
                ->orderByDesc('occurred_at')
                ->orderByDesc('id')
                ->first();
            if ($sameConn) {
                return $sameConn;
            }
        }

        return $base->orderByDesc('occurred_at')->orderByDesc('id')->first();
    }

    private function latestMessageWithParticipant(?int $companyId, string $peerPhone, ?int $connectionId): ?WhatsAppMessage
    {
        $base = WhatsAppMessage::withoutGlobalScope('company')
            ->where('peer_phone', $peerPhone)
            ->whereNotNull('participant_type')
            ->whereNotNull('participant_id')
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->when(! $companyId, fn ($q) => $q->whereNull('company_id'));

        if ($connectionId) {
            $sameConn = (clone $base)
                ->where('whatsapp_connection_id', $connectionId)
                ->orderByDesc('occurred_at')
                ->orderByDesc('id')
                ->first();
            if ($sameConn) {
                return $sameConn;
            }
        }

        return $base->orderByDesc('occurred_at')->orderByDesc('id')->first();
    }

    /**
     * @return array{participant: ?Model, participant_type: ?string, participant_id: ?int, participant_conflict: ?array}
     */
    private function resolveFromLatestParticipant(WhatsAppMessage $latest, string $peerPhone, ?int $companyId): array
    {
        $participant = $this->loadParticipant($latest->participant_type, (int) $latest->participant_id);

        if ($participant && $this->modelOwnsPhone($participant, $peerPhone, $latest->participant_type)) {
            return [
                'participant' => $participant,
                'participant_type' => $latest->participant_type,
                'participant_id' => (int) $latest->participant_id,
                'participant_conflict' => null,
            ];
        }

        $lookup = $this->registryLookup($peerPhone, $companyId);
        if ($lookup['participant'] || $lookup['participant_conflict']) {
            return $lookup;
        }

        // Keep stale / trashed participant when no new owner found.
        return [
            'participant' => $participant,
            'participant_type' => $latest->participant_type,
            'participant_id' => (int) $latest->participant_id,
            'participant_conflict' => null,
        ];
    }

    /**
     * @return array{participant: ?Model, participant_type: ?string, participant_id: ?int, participant_conflict: ?array}
     */
    private function registryLookup(string $peerPhone, ?int $companyId): array
    {
        $matches = $this->findAllByPhone($peerPhone, $companyId);

        if ($matches->count() === 1) {
            /** @var array{model: Model, morph: string, key: string} $hit */
            $hit = $matches->first();

            return [
                'participant' => $hit['model'],
                'participant_type' => $hit['morph'],
                'participant_id' => (int) $hit['model']->getKey(),
                'participant_conflict' => null,
            ];
        }

        if ($matches->count() > 1) {
            $candidates = $matches->map(function (array $hit) {
                $model = $hit['model'];

                return [
                    'type' => $hit['morph'],
                    'filter_key' => $hit['key'],
                    'id' => (int) $model->getKey(),
                    'label' => WhatsAppRecipientTypes::displayName($model),
                    'trashed' => WhatsAppRecipientTypes::isTrashed($model),
                ];
            })->values()->all();

            return [
                'participant' => null,
                'participant_type' => null,
                'participant_id' => null,
                'participant_conflict' => ['candidates' => $candidates],
            ];
        }

        return [
            'participant' => null,
            'participant_type' => null,
            'participant_id' => null,
            'participant_conflict' => null,
        ];
    }

    /**
     * @return Collection<int, array{model: Model, morph: string, key: string}>
     */
    private function findAllByPhone(string $peerPhone, ?int $companyId): Collection
    {
        $hits = collect();

        foreach (WhatsAppRecipientTypes::definitions() as $key => $def) {
            if ($key === WhatsAppRecipientTypes::UNKNOWN || ! $def['model'] || ! $def['morph']) {
                continue;
            }

            $query = WhatsAppRecipientTypes::baseQuery($key);
            if (! $query) {
                continue;
            }

            // Webhooks have no company URL context; BelongsToCompany would
            // otherwise apply `0 = 1` and hide every tenant row.
            $query->withoutGlobalScope('company');

            if ($companyId && $this->modelHasCompanyId($def['model'])) {
                $query->where($query->getModel()->qualifyColumn('company_id'), $companyId);
            }

            $table = (new $def['model'])->getTable();
            $fields = array_values(array_filter(
                $def['phone_fields'] ?? [],
                fn (string $field) => Schema::hasColumn($table, $field)
            ));
            if ($fields === []) {
                continue;
            }

            $localUae = str_starts_with($peerPhone, '971') && strlen($peerPhone) >= 12
                ? '0'.substr($peerPhone, 3)
                : null;
            $suffix = strlen($peerPhone) > 9 ? substr($peerPhone, -9) : $peerPhone;

            $query->where(function ($q) use ($fields, $peerPhone, $localUae, $suffix) {
                foreach ($fields as $field) {
                    $q->orWhere(function ($inner) use ($field, $peerPhone, $localUae, $suffix) {
                        $inner->where($field, $peerPhone)
                            ->orWhere($field, '+'.$peerPhone)
                            ->orWhere($field, 'like', '%'.$suffix.'%');
                        if ($localUae) {
                            $inner->orWhere($field, $localUae);
                        }
                    });
                }
            });

            foreach ($query->limit(50)->get() as $model) {
                if ($this->modelOwnsPhone($model, $peerPhone, $def['morph'])) {
                    $hits->push([
                        'model' => $model,
                        'morph' => $def['morph'],
                        'key' => $key,
                    ]);
                }
            }
        }

        return $hits->unique(fn ($h) => $h['morph'].':'.$h['model']->getKey())->values();
    }

    private function modelOwnsPhone(Model $model, string $peerPhone, ?string $morphAlias): bool
    {
        $key = WhatsAppRecipientTypes::keyForMorph($morphAlias);
        $fields = $key ? (WhatsAppRecipientTypes::definitions()[$key]['phone_fields'] ?? []) : [];

        if ($fields === [] && $model instanceof User) {
            $fields = ['company_contact', 'phone'];
        }

        $table = $model->getTable();
        foreach ($fields as $field) {
            if (! Schema::hasColumn($table, $field)) {
                continue;
            }
            $digits = PhoneNumber::toWhatsAppDigits((string) ($model->{$field} ?? ''));
            if ($digits && $digits === $peerPhone) {
                return true;
            }
        }

        return false;
    }

    private function loadParticipant(?string $type, int $id): ?Model
    {
        if (! $type || $id <= 0) {
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

        return $query->withoutGlobalScope('company')->whereKey($id)->first();
    }

    /**
     * Last non-null inbox owner for this peer, else the configured unknown assignee.
     */
    private function continuityUserId(?int $companyId, string $peerPhone, ?int $connectionId): ?int
    {
        $ownedId = WhatsAppMessage::withoutGlobalScope('company')
            ->where('peer_phone', $peerPhone)
            ->whereNotNull('user_id')
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->when(! $companyId, fn ($q) => $q->whereNull('company_id'))
            ->when($connectionId, fn ($q) => $q->where('whatsapp_connection_id', $connectionId))
            ->orderByDesc('occurred_at')
            ->orderByDesc('id')
            ->value('user_id');

        if ($ownedId) {
            return (int) $ownedId;
        }

        if ($companyId && $connectionId) {
            return $this->unknownAssignee((int) $companyId, (int) $connectionId);
        }

        return null;
    }

    private function unknownAssignee(int $companyId, int $connectionId): ?int
    {
        $setting = WhatsAppUnknownInboxSetting::withoutGlobalScope('company')
            ->where('company_id', $companyId)
            ->where('whatsapp_connection_id', $connectionId)
            ->first();

        return $setting && $setting->user_id ? (int) $setting->user_id : null;
    }

    private function sanitizeUserId(?int $userId, ?int $companyId, ?int $connectionId): ?int
    {
        $fallback = ($companyId && $connectionId)
            ? $this->unknownAssignee((int) $companyId, (int) $connectionId)
            : null;

        if (! $userId) {
            return $fallback;
        }

        // Webhooks/jobs often have no company context; never hide the owner lookup.
        $user = User::withoutGlobalScope('company')->whereKey($userId)->first();
        if (! $user) {
            return $fallback;
        }

        $ok = false;
        try {
            $ok = $user->can('whatsapp_view')
                || (method_exists($user, 'isAdmin') && $user->isAdmin())
                || $user->hasAnyRole(['Administrator', 'Super Admin'])
                || ($fallback !== null && (int) $userId === (int) $fallback);
        } catch (\Throwable $e) {
            $ok = true;
        }

        if ($ok) {
            return $userId;
        }

        return $fallback;
    }

    private function modelHasCompanyId(string $modelClass): bool
    {
        $model = new $modelClass;

        return in_array('company_id', $model->getFillable(), true)
            || \Illuminate\Support\Facades\Schema::hasColumn($model->getTable(), 'company_id');
    }
}
