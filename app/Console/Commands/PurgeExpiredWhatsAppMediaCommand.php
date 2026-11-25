<?php

namespace App\Console\Commands;

use App\Services\WhatsApp\WhatsAppMediaService;
use Illuminate\Console\Command;

class PurgeExpiredWhatsAppMediaCommand extends Command
{
    protected $signature = 'whatsapp:purge-expired-media';

    protected $description = 'Delete WhatsApp media files older than the configured TTL and clear media_path';

    public function handle(WhatsAppMediaService $media): int
    {
        $purged = $media->purgeExpired();
        $this->info("Purged {$purged} expired WhatsApp media file(s). TTL={$media->ttlDays()} day(s).");

        return self::SUCCESS;
    }
}
