<?php

namespace App\Services\WhatsApp;

use App\Models\NotificationRule;
use App\Models\User;
use App\Models\WhatsAppConnection;
use App\Services\Notifications\CompanyNotificationChannels;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class WhatsAppConnectionService
{
    /**
     * First visible number for a company (assigned, else platform default).
     * Prefer an explicit connection id for sends — do not use this for notification rules.
     */
    public function resolveForCompany(int $companyId, bool $requireEntitlement = true): ?WhatsAppConnection
    {
        return $this->getConnectionsVisibleToCompany($companyId, $requireEntitlement)->first();
    }

    /**
     * Numbers available to a WhatsApp-entitled company: active company-assigned
     * numbers, or the platform default when the company has none assigned.
     */
    public function getConnectionsVisibleToCompany(int $companyId, bool $requireEntitlement = true): Collection
    {
        if ($companyId <= 0) {
            return new Collection();
        }

        if ($requireEntitlement && ! CompanyNotificationChannels::isEnabled(
            $companyId,
            CompanyNotificationChannels::CHANNEL_WHATSAPP
        )) {
            return new Collection();
        }

        $assigned = $this->getActiveCompanyConnections($companyId);
        if ($assigned->isNotEmpty()) {
            return $assigned->values();
        }

        $default = WhatsAppConnection::query()
            ->active()
            ->defaultNumber()
            ->orderBy('id')
            ->first();

        return $default ? new Collection([$default]) : new Collection();
    }

    /**
     * Active connections that exist for the company (assigned only, no default fallback).
     */
    public function getActiveCompanyConnections(?int $companyId = null): Collection
    {
        $companyId = $companyId ?: (int) (\App\Support\CompanyContext::id() ?? 0);
        $query = WhatsAppConnection::query()->active();
        if ($companyId > 0) {
            $query->forCompany($companyId);
        }

        return $query->orderBy('label')->orderBy('id')->get();
    }

    /**
     * Connections a specific user may manually send from (strict pivot).
     * - Company with assigned numbers: only pivot-allotted company numbers (never default).
     * - Default-only company: empty (no tenant manual send).
     * Admins are not exempt.
     */
    public function getConnectionsForUserSend(User $user, ?int $companyId = null): Collection
    {
        $companyId = $companyId ?: (int) (\App\Support\CompanyContext::id() ?? $user->company_id ?? 0);
        if ($companyId <= 0) {
            return new Collection();
        }

        if (! CompanyNotificationChannels::isEnabled(
            $companyId,
            CompanyNotificationChannels::CHANNEL_WHATSAPP
        )) {
            return new Collection();
        }

        $assigned = $this->getActiveCompanyConnections($companyId);
        if ($assigned->isEmpty()) {
            return new Collection();
        }

        $assignedIds = $assigned->pluck('id')->map(fn ($id) => (int) $id)->all();
        $allowedIds = $user->whatsappConnections()
            ->whereIn('whatsapp_connections.id', $assignedIds)
            ->pluck('whatsapp_connections.id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if ($allowedIds === []) {
            return new Collection();
        }

        return $assigned->filter(fn (WhatsAppConnection $c) => in_array((int) $c->id, $allowedIds, true))->values();
    }

    /**
     * Numbers for pickers that allow automation selection (notification rules, etc.).
     * Assigned companies: company numbers only (no default).
     * Default-only: platform default (for automation config).
     */
    public function getConnectionsForCompanyAutomation(int $companyId, bool $requireEntitlement = true): Collection
    {
        return $this->getConnectionsVisibleToCompany($companyId, $requireEntitlement);
    }

    /**
     * @deprecated Use getConnectionsForUserSend for manual sends.
     */
    public function getActiveAssignedConnections(User $user, ?int $companyId = null): Collection
    {
        return $this->getConnectionsForUserSend($user, $companyId);
    }

    public function userCanUseConnection(User $user, WhatsAppConnection $connection): bool
    {
        if (! $connection->isActive()) {
            return false;
        }

        // Platform default is never a tenant manual send target.
        if ($connection->is_default || $connection->company_id === null) {
            return false;
        }

        $companyId = (int) (\App\Support\CompanyContext::id() ?? $user->company_id ?? 0);
        if ($companyId <= 0 || (int) $connection->company_id !== $companyId) {
            return false;
        }

        if (! CompanyNotificationChannels::isEnabled(
            $companyId,
            CompanyNotificationChannels::CHANNEL_WHATSAPP
        )) {
            return false;
        }

        return $user->whatsappConnections()
            ->where('whatsapp_connections.id', $connection->id)
            ->exists();
    }

    public function userHasAnySendNumber(User $user, ?int $companyId = null): bool
    {
        return $this->getConnectionsForUserSend($user, $companyId)->isNotEmpty();
    }

    /**
     * When a company gains assigned numbers, clear notification rules still pointing at default.
     */
    public function stripDefaultConnectionFromCompanyRules(int $companyId): void
    {
        if ($companyId <= 0) {
            return;
        }

        $defaultIds = WhatsAppConnection::query()
            ->defaultNumber()
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if ($defaultIds === []) {
            return;
        }

        $rules = NotificationRule::query()
            ->where('company_id', $companyId)
            ->get();

        foreach ($rules as $rule) {
            $config = is_array($rule->config) ? $rule->config : [];
            $connId = isset($config['whatsapp_connection_id']) ? (int) $config['whatsapp_connection_id'] : 0;
            if ($connId > 0 && in_array($connId, $defaultIds, true)) {
                unset($config['whatsapp_connection_id']);
                $rule->config = $config;
                $rule->save();
            }
        }
    }

    /**
     * @return array{ready: bool, message?: string, status?: int, connection?: WhatsAppConnection}
     */
    public function prepareUserConnection(User $actor, int $connectionId): array
    {
        $connection = WhatsAppConnection::query()->find($connectionId);
        if (! $connection || ! $connection->isActive()) {
            return [
                'ready' => false,
                'message' => 'The selected WhatsApp number is not available.',
                'status' => 422,
            ];
        }

        if (! $this->userCanUseConnection($actor, $connection)) {
            return [
                'ready' => false,
                'message' => 'You cannot use the selected WhatsApp number.',
                'status' => 403,
            ];
        }

        return $this->ensureCredentials($connection);
    }

    /**
     * Admin / system send: any active number by id (no company user assignment).
     *
     * @return array{ready: bool, message?: string, status?: int, connection?: WhatsAppConnection}
     */
    public function prepareConnectionById(int $connectionId): array
    {
        $connection = WhatsAppConnection::query()->find($connectionId);
        if (! $connection || ! $connection->isActive()) {
            return [
                'ready' => false,
                'message' => 'The selected WhatsApp number is not available.',
                'status' => 422,
            ];
        }

        return $this->ensureCredentials($connection);
    }

    /**
     * Resolve an active connection for system/automated sends.
     * Requires an explicit connection id — does not auto-pick company/default numbers.
     *
     * @return array{ready: bool, message?: string, connection?: WhatsAppConnection|null}
     */
    public function prepareSystemConnection(?int $connectionId, ?int $companyId = null): array
    {
        if (! $connectionId || $connectionId <= 0) {
            return [
                'ready' => false,
                'message' => 'No WhatsApp number was selected for this send.',
                'connection' => null,
            ];
        }

        $connection = WhatsAppConnection::query()
            ->active()
            ->whereKey($connectionId)
            ->first();

        // When a company context is provided, the number must be visible to that company.
        if ($connection && $companyId && $companyId > 0) {
            $visible = $this->getConnectionsVisibleToCompany((int) $companyId, true)
                ->contains(fn (WhatsAppConnection $c) => (int) $c->id === (int) $connection->id);
            if (! $visible) {
                $connection = null;
            }
        }

        if (! $connection) {
            return [
                'ready' => false,
                'message' => 'The selected WhatsApp number is not available for this company.',
                'connection' => null,
            ];
        }

        $ready = $this->ensureCredentials($connection);
        if (! ($ready['ready'] ?? false)) {
            return [
                'ready' => false,
                'message' => $ready['message'] ?? 'Failed to load WhatsApp credentials for the selected number.',
                'connection' => $connection,
            ];
        }

        return [
            'ready' => true,
            'connection' => $connection,
        ];
    }

    /**
     * Persist assignment rules: company XOR default; single platform default.
     * Multiple numbers may be assigned to the same company.
     *
     * @param  array{company_id?: int|null, is_default?: bool}  $assignment
     */
    public function applyAssignment(WhatsAppConnection $connection, array $assignment): void
    {
        $isDefault = (bool) ($assignment['is_default'] ?? false);
        $companyId = $assignment['company_id'] ?? null;
        $companyId = $companyId !== null && $companyId !== '' ? (int) $companyId : null;

        if ($isDefault) {
            $companyId = null;
        }

        $previousCompanyId = $connection->company_id ? (int) $connection->company_id : null;

        DB::transaction(function () use ($connection, $isDefault, $companyId) {
            if ($isDefault) {
                WhatsAppConnection::query()
                    ->where('is_default', true)
                    ->when($connection->exists, fn ($q) => $q->where('id', '!=', $connection->id))
                    ->update(['is_default' => false]);
            }

            $connection->is_default = $isDefault;
            $connection->company_id = $companyId;
            $connection->save();
        });

        // Newly assigned company number → strip default from that company's notification rules.
        if ($companyId && $companyId > 0) {
            $this->stripDefaultConnectionFromCompanyRules($companyId);
        }

        // If removed from a company that still has other assigned numbers, still strip defaults there.
        if ($previousCompanyId && $previousCompanyId !== $companyId
            && $this->getActiveCompanyConnections($previousCompanyId)->isNotEmpty()) {
            $this->stripDefaultConnectionFromCompanyRules($previousCompanyId);
        }
    }

    /**
     * @return array{ready: bool, message?: string, status?: int, connection?: WhatsAppConnection}
     */
    private function ensureCredentials(WhatsAppConnection $connection): array
    {
        if (! is_string($connection->getDecryptedAccessToken()) || trim((string) $connection->getDecryptedAccessToken()) === '') {
            return [
                'ready' => false,
                'message' => 'Failed to load WhatsApp credentials. Re-save the number and try again.',
                'status' => 422,
                'connection' => $connection,
            ];
        }

        return [
            'ready' => true,
            'connection' => $connection,
            'status' => 200,
        ];
    }
}
