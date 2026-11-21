<?php

namespace App\Services\WhatsApp\Context;

use App\Support\WhatsApp\WhatsAppSendContext;

/**
 * Builds a typed send context for a trigger. Callers must not invent inject fields ad hoc.
 */
interface WhatsAppContextBuilder
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function build(array $payload): WhatsAppSendContext;

    /**
     * Inject keys this builder can populate (subset of config inject_keys).
     *
     * @return list<string>
     */
    public function provides(): array;
}
