<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WhatsAppConnection;
use App\Services\WhatsApp\WhatsAppConnectionService;

/**
 * Company-side policy. CRUD for numbers is admin-only (admin panel routes).
 * Company users may only "send" via the number resolved for their company.
 */
class WhatsAppConnectionPolicy
{
    public function viewAny(User $user): bool
    {
        return false;
    }

    public function view(User $user, WhatsAppConnection $whatsappConnection): bool
    {
        return false;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, WhatsAppConnection $whatsappConnection): bool
    {
        return false;
    }

    public function delete(User $user, WhatsAppConnection $whatsappConnection): bool
    {
        return false;
    }

    public function send(User $user, WhatsAppConnection $whatsappConnection): bool
    {
        return app(WhatsAppConnectionService::class)
            ->userCanUseConnection($user, $whatsappConnection);
    }
}
