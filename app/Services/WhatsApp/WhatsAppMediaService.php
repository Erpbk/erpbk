<?php

namespace App\Services\WhatsApp;

use App\Models\WhatsAppConnection;
use App\Models\WhatsAppMessage;
use App\Support\PublicStorageDisk;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class WhatsAppMediaService
{
    public const TYPES = ['image', 'audio', 'video', 'document'];

    public function __construct(
        protected WhatsAppCloudApiClient $client
    ) {
    }

    public function ttlDays(): int
    {
        $days = (int) config('whatsapp.media_ttl_days', 3);

        return max(1, $days);
    }

    public function cacheIsFresh(?WhatsAppMessage $message): bool
    {
        if (! $message || ! $message->media_path || ! $message->media_cached_at) {
            return false;
        }

        if (! PublicStorageDisk::exists($message->media_path)) {
            return false;
        }

        return $message->media_cached_at->gt(now()->subDays($this->ttlDays()));
    }

    /**
     * @return array{ready: bool, downloadable: bool, url: ?string, mime: ?string, filename: ?string, size: ?int, cached_until: ?string}
     */
    public function serializeMedia(WhatsAppMessage $message): array
    {
        $ready = $this->cacheIsFresh($message);
        $type = (string) ($message->message_type ?: '');
        $isMedia = in_array($type, self::TYPES, true);
        $hasMetaId = is_string($message->media_id) && trim($message->media_id) !== '';

        $cachedUntil = null;
        if ($ready && $message->media_cached_at) {
            $cachedUntil = $message->media_cached_at->copy()->addDays($this->ttlDays())->toIso8601String();
        }

        return [
            'ready' => $ready,
            'downloadable' => $isMedia && $hasMetaId && ! $ready,
            'url' => $ready ? PublicStorageDisk::url($message->media_path) : null,
            'mime' => $message->media_mime,
            'filename' => $message->media_filename,
            'size' => $message->media_size ? (int) $message->media_size : null,
            'cached_until' => $cachedUntil,
        ];
    }

    /**
     * Fetch from Meta (if needed) and cache on public disk.
     *
     * @return array{ok: bool, ready?: bool, url?: ?string, mime?: ?string, filename?: ?string, size?: ?int, cached_until?: ?string, error?: string}
     */
    public function ensureCached(WhatsAppMessage $message): array
    {
        if ($this->cacheIsFresh($message)) {
            $media = $this->serializeMedia($message);

            return array_merge(['ok' => true], $media);
        }

        // Clear stale path if file/TTL gone.
        if ($message->media_path) {
            PublicStorageDisk::delete($message->media_path);
            $message->forceFill([
                'media_path' => null,
                'media_cached_at' => null,
            ])->save();
        }

        $mediaId = trim((string) ($message->media_id ?? ''));
        if ($mediaId === '') {
            return ['ok' => false, 'error' => 'No WhatsApp media id on this message.'];
        }

        $connection = $message->connection
            ?? WhatsAppConnection::query()->find($message->whatsapp_connection_id);
        if (! $connection instanceof WhatsAppConnection) {
            return ['ok' => false, 'error' => 'WhatsApp connection is missing for this message.'];
        }

        $downloaded = $this->client->downloadMedia($connection, $mediaId);
        if (! ($downloaded['ok'] ?? false)) {
            return [
                'ok' => false,
                'error' => (string) ($downloaded['error'] ?? 'Media no longer available from WhatsApp.'),
            ];
        }

        $mime = (string) ($downloaded['mime'] ?? $message->media_mime ?? 'application/octet-stream');
        $filename = $this->safeFilename(
            $message->media_filename
                ?: ($downloaded['filename'] ?? null)
                ?: ($this->extensionForMime($mime) ? ('file.'.$this->extensionForMime($mime)) : 'file.bin')
        );
        $bytes = (string) ($downloaded['bytes'] ?? '');
        if ($bytes === '') {
            return ['ok' => false, 'error' => 'Downloaded media was empty.'];
        }

        $companyKey = $message->company_id ? (string) $message->company_id : 'default';
        $path = sprintf('whatsapp/%s/%d/%s', $companyKey, $message->id, $filename);

        if (! PublicStorageDisk::put($path, $bytes)) {
            return ['ok' => false, 'error' => 'Failed to store media on disk.'];
        }

        $message->forceFill([
            'media_path' => $path,
            'media_size' => strlen($bytes),
            'media_mime' => $mime,
            'media_filename' => $filename,
            'media_cached_at' => now(),
        ])->save();

        $media = $this->serializeMedia($message);

        return array_merge(['ok' => true], $media);
    }

    /**
     * @return array{ok: bool, type?: string, mime?: string, filename?: string, path?: string, size?: int, error?: string}
     */
    public function storeOutboundUpload(UploadedFile $file, int $companyId): array
    {
        $mime = strtolower((string) ($file->getMimeType() ?: ''));
        $type = $this->typeForMime($mime);
        if ($type === null) {
            $ext = strtolower((string) $file->getClientOriginalExtension());
            $type = $this->typeForExtension($ext);
            if ($type) {
                $mime = $this->preferredMimeForExtension($ext) ?: $mime;
            }
        }

        if ($type === null) {
            return ['ok' => false, 'error' => 'Unsupported media type.'];
        }

        $maxKb = (int) config("whatsapp.media.{$type}.max_kb", 5120);
        if ($file->getSize() > $maxKb * 1024) {
            return ['ok' => false, 'error' => 'File exceeds the maximum size for '.$type.'.'];
        }

        $filename = $this->safeFilename($file->getClientOriginalName() ?: ('file.'.$this->extensionForMime($mime)));
        $companyKey = $companyId > 0 ? (string) $companyId : 'default';
        $directory = sprintf('whatsapp/%s/outbound/%s', $companyKey, Str::lower(Str::random(16)));
        $path = PublicStorageDisk::storeUploadedFile($file, $directory, $filename);

        return [
            'ok' => true,
            'type' => $type,
            'mime' => $mime !== '' ? $mime : (string) config("whatsapp.media.{$type}.mimes.0"),
            'filename' => $filename,
            'path' => $path,
            'size' => (int) $file->getSize(),
        ];
    }

    public function purgeExpired(): int
    {
        $cutoff = now()->subDays($this->ttlDays());
        $purged = 0;

        WhatsAppMessage::withoutGlobalScope('company')
            ->whereNotNull('media_path')
            ->whereNotNull('media_cached_at')
            ->where('media_cached_at', '<', $cutoff)
            ->orderBy('id')
            ->chunkById(100, function ($rows) use (&$purged) {
                foreach ($rows as $message) {
                    /** @var WhatsAppMessage $message */
                    PublicStorageDisk::delete($message->media_path);
                    $message->forceFill([
                        'media_path' => null,
                        'media_cached_at' => null,
                    ])->save();
                    $purged++;
                }
            });

        return $purged;
    }

    /**
     * Extract inbound media metadata from a Meta webhook message object.
     *
     * @param  array<string, mixed>  $message
     * @return array{media_id: ?string, media_mime: ?string, media_filename: ?string, media_sha256: ?string, caption: ?string}
     */
    public function extractInboundMeta(string $type, array $message): array
    {
        if (! in_array($type, self::TYPES, true)) {
            return [
                'media_id' => null,
                'media_mime' => null,
                'media_filename' => null,
                'media_sha256' => null,
                'caption' => null,
            ];
        }

        $block = is_array($message[$type] ?? null) ? $message[$type] : [];

        return [
            'media_id' => isset($block['id']) ? (string) $block['id'] : null,
            'media_mime' => isset($block['mime_type']) ? (string) $block['mime_type'] : null,
            'media_filename' => isset($block['filename']) ? $this->safeFilename((string) $block['filename']) : null,
            'media_sha256' => isset($block['sha256']) ? (string) $block['sha256'] : null,
            'caption' => isset($block['caption']) ? (string) $block['caption'] : null,
        ];
    }

    public function typeForMime(string $mime): ?string
    {
        $mime = strtolower(trim($mime));
        if ($mime === '') {
            return null;
        }

        foreach (self::TYPES as $type) {
            $allowed = config("whatsapp.media.{$type}.mimes", []);
            if (is_array($allowed) && in_array($mime, $allowed, true)) {
                return $type;
            }
        }

        // Ogg voice notes sometimes report as audio/ogg; opus codec.
        if (str_starts_with($mime, 'audio/')) {
            return 'audio';
        }
        if (str_starts_with($mime, 'image/')) {
            return 'image';
        }
        if (str_starts_with($mime, 'video/')) {
            return 'video';
        }

        return null;
    }

    public function typeForExtension(string $ext): ?string
    {
        $ext = strtolower(ltrim($ext, '.'));
        foreach (self::TYPES as $type) {
            $allowed = config("whatsapp.media.{$type}.extensions", []);
            if (is_array($allowed) && in_array($ext, $allowed, true)) {
                return $type;
            }
        }

        return null;
    }

    public function extensionForMime(string $mime): ?string
    {
        $map = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'audio/aac' => 'aac',
            'audio/mp4' => 'm4a',
            'audio/mpeg' => 'mp3',
            'audio/amr' => 'amr',
            'audio/ogg' => 'ogg',
            'audio/opus' => 'ogg',
            'video/mp4' => 'mp4',
            'video/3gpp' => '3gp',
            'application/pdf' => 'pdf',
            'text/plain' => 'txt',
        ];

        return $map[strtolower($mime)] ?? null;
    }

    public function preferredMimeForExtension(string $ext): ?string
    {
        $map = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'webp' => 'image/webp',
            'aac' => 'audio/aac',
            'm4a' => 'audio/mp4',
            'mp3' => 'audio/mpeg',
            'amr' => 'audio/amr',
            'ogg' => 'audio/ogg',
            'opus' => 'audio/ogg',
            'mp4' => 'video/mp4',
            '3gp' => 'video/3gpp',
            'pdf' => 'application/pdf',
            'txt' => 'text/plain',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls' => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'ppt' => 'application/vnd.ms-powerpoint',
            'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        ];

        return $map[strtolower(ltrim($ext, '.'))] ?? null;
    }

    public function safeFilename(?string $name): string
    {
        $name = basename((string) $name);
        $name = preg_replace('/[^\w.\- ()\[\]]+/u', '_', $name) ?: 'file.bin';
        $name = trim($name, '._ ');

        if ($name === '') {
            $name = 'file.bin';
        }

        return Str::limit($name, 180, '');
    }

    public function markCached(WhatsAppMessage $message, string $path, ?string $mime, ?string $filename, ?int $size): void
    {
        $message->forceFill([
            'media_path' => $path,
            'media_mime' => $mime,
            'media_filename' => $filename,
            'media_size' => $size,
            'media_cached_at' => now(),
        ])->save();
    }
}
