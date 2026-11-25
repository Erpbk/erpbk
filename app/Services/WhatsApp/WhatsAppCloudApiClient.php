<?php

namespace App\Services\WhatsApp;

use App\Models\WhatsAppConnection;
use App\Support\PhoneNumber;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class WhatsAppCloudApiClient
{
    /**
     * @return array{ok: bool, message_id?: string|null, response?: array<string, mixed>, error?: string, status?: int}
     */
    public function sendText(WhatsAppConnection $connection, string $toE164, string $body): array
    {
        $to = PhoneNumber::toWhatsAppDigits($toE164);
        if (! PhoneNumber::isValidWhatsAppDigits($to)) {
            return ['ok' => false, 'error' => 'Invalid recipient phone number.'];
        }

        $body = trim($body);
        if ($body === '') {
            return ['ok' => false, 'error' => 'Message body is required.'];
        }

        return $this->postMessage($connection, [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $to,
            'type' => 'text',
            'text' => [
                'preview_url' => false,
                'body' => $body,
            ],
        ]);
    }

    /**
     * @param  list<array<string, mixed>>  $components
     * @return array{ok: bool, message_id?: string|null, response?: array<string, mixed>, error?: string, status?: int}
     */
    public function sendTemplate(
        WhatsAppConnection $connection,
        string $toE164,
        string $templateName,
        string $language = 'en',
        array $components = []
    ): array {
        $to = PhoneNumber::toWhatsAppDigits($toE164);
        if (! PhoneNumber::isValidWhatsAppDigits($to)) {
            return ['ok' => false, 'error' => 'Invalid recipient phone number.'];
        }

        $templateName = trim($templateName);
        if ($templateName === '') {
            return ['ok' => false, 'error' => 'Template name is required.'];
        }

        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $to,
            'type' => 'template',
            'template' => [
                'name' => $templateName,
                'language' => [
                    'code' => $language !== '' ? $language : 'en',
                ],
            ],
        ];

        if ($components !== []) {
            $payload['template']['components'] = $components;
        }

        return $this->postMessage($connection, $payload);
    }

    /**
     * @return array{ok: bool, message_id?: string|null, response?: array<string, mixed>, error?: string, status?: int}
     */
    public function sendImage(
        WhatsAppConnection $connection,
        string $toE164,
        string $mediaId,
        ?string $caption = null
    ): array {
        return $this->sendMediaMessage($connection, $toE164, 'image', $mediaId, $caption, null);
    }

    /**
     * @return array{ok: bool, message_id?: string|null, response?: array<string, mixed>, error?: string, status?: int}
     */
    public function sendAudio(WhatsAppConnection $connection, string $toE164, string $mediaId): array
    {
        return $this->sendMediaMessage($connection, $toE164, 'audio', $mediaId, null, null);
    }

    /**
     * @return array{ok: bool, message_id?: string|null, response?: array<string, mixed>, error?: string, status?: int}
     */
    public function sendVideo(
        WhatsAppConnection $connection,
        string $toE164,
        string $mediaId,
        ?string $caption = null
    ): array {
        return $this->sendMediaMessage($connection, $toE164, 'video', $mediaId, $caption, null);
    }

    /**
     * @return array{ok: bool, message_id?: string|null, response?: array<string, mixed>, error?: string, status?: int}
     */
    public function sendDocument(
        WhatsAppConnection $connection,
        string $toE164,
        string $mediaId,
        ?string $filename = null,
        ?string $caption = null
    ): array {
        return $this->sendMediaMessage($connection, $toE164, 'document', $mediaId, $caption, $filename);
    }

    /**
     * Upload a local file to Meta media API.
     *
     * @return array{ok: bool, media_id?: string|null, response?: array<string, mixed>, error?: string, status?: int}
     */
    public function uploadMedia(
        WhatsAppConnection $connection,
        string $absoluteOrDiskPath,
        string $mime,
        ?string $filename = null
    ): array {
        $auth = $this->authContext($connection);
        if (! ($auth['ok'] ?? false)) {
            return ['ok' => false, 'error' => (string) ($auth['error'] ?? 'Auth failed.')];
        }

        $contents = null;
        $path = $absoluteOrDiskPath;
        if (is_file($path) && is_readable($path)) {
            $contents = file_get_contents($path);
        } else {
            $readable = \App\Support\PublicStorageDisk::readablePath($absoluteOrDiskPath);
            if ($readable) {
                $contents = file_get_contents($readable);
                if (\App\Support\PublicStorageDisk::isCloud() && is_file($readable)) {
                    @unlink($readable);
                }
            }
        }

        if (! is_string($contents) || $contents === '') {
            return ['ok' => false, 'error' => 'Media file could not be read for upload.'];
        }

        $filename = $filename ?: (basename($absoluteOrDiskPath) ?: 'upload.bin');
        $version = trim((string) config('whatsapp.graph_version', 'v21.0'));
        $url = sprintf(
            'https://graph.facebook.com/%s/%s/media',
            $version,
            $auth['phone_number_id']
        );

        try {
            $response = Http::timeout(60)
                ->withToken($auth['token'])
                ->acceptJson()
                ->attach('file', $contents, $filename, ['Content-Type' => $mime])
                ->post($url, [
                    'messaging_product' => 'whatsapp',
                    'type' => $mime,
                ]);

            $json = $response->json();
            if (! is_array($json)) {
                $json = ['raw' => $response->body()];
            }

            if (! $response->successful()) {
                $error = data_get($json, 'error.message')
                    ?? data_get($json, 'error.error_user_msg')
                    ?? ('Meta media upload error HTTP '.$response->status());

                Log::warning('whatsapp.media_upload_failed', [
                    'connection_id' => $connection->id,
                    'status' => $response->status(),
                    'error' => $error,
                ]);

                return [
                    'ok' => false,
                    'error' => (string) $error,
                    'status' => $response->status(),
                    'response' => $json,
                ];
            }

            $mediaId = data_get($json, 'id');

            return [
                'ok' => true,
                'media_id' => is_string($mediaId) ? $mediaId : null,
                'response' => $json,
                'status' => $response->status(),
            ];
        } catch (\Throwable $e) {
            Log::error('whatsapp.media_upload_exception', [
                'connection_id' => $connection->id,
                'error' => $e->getMessage(),
            ]);

            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Download media bytes from Meta using a media id.
     *
     * @return array{ok: bool, bytes?: string, mime?: string|null, filename?: string|null, error?: string}
     */
    public function downloadMedia(WhatsAppConnection $connection, string $mediaId): array
    {
        $mediaId = trim($mediaId);
        if ($mediaId === '') {
            return ['ok' => false, 'error' => 'Media id is required.'];
        }

        $meta = $this->getMediaUrl($connection, $mediaId);
        if (! ($meta['ok'] ?? false)) {
            return [
                'ok' => false,
                'error' => (string) ($meta['error'] ?? 'Media no longer available from WhatsApp.'),
            ];
        }

        $auth = $this->authContext($connection);
        if (! ($auth['ok'] ?? false)) {
            return ['ok' => false, 'error' => (string) ($auth['error'] ?? 'Auth failed.')];
        }

        try {
            $response = Http::timeout(60)
                ->withToken($auth['token'])
                ->withHeaders(['User-Agent' => 'erpbk-whatsapp-media'])
                ->get((string) $meta['url']);

            if (! $response->successful()) {
                Log::warning('whatsapp.media_binary_failed', [
                    'connection_id' => $connection->id,
                    'media_id' => $mediaId,
                    'status' => $response->status(),
                ]);

                return [
                    'ok' => false,
                    'error' => 'Media no longer available from WhatsApp.',
                ];
            }

            $bytes = $response->body();
            if ($bytes === '' || $bytes === null) {
                return ['ok' => false, 'error' => 'Downloaded media was empty.'];
            }

            return [
                'ok' => true,
                'bytes' => $bytes,
                'mime' => $meta['mime'] ?? $response->header('Content-Type'),
                'filename' => null,
            ];
        } catch (\Throwable $e) {
            Log::error('whatsapp.media_download_exception', [
                'connection_id' => $connection->id,
                'media_id' => $mediaId,
                'error' => $e->getMessage(),
            ]);

            return ['ok' => false, 'error' => 'Media no longer available from WhatsApp.'];
        }
    }

    /**
     * @return array{ok: bool, url?: string, mime?: string|null, error?: string, status?: int}
     */
    public function getMediaUrl(WhatsAppConnection $connection, string $mediaId): array
    {
        $auth = $this->authContext($connection);
        if (! ($auth['ok'] ?? false)) {
            return ['ok' => false, 'error' => (string) ($auth['error'] ?? 'Auth failed.')];
        }

        $version = trim((string) config('whatsapp.graph_version', 'v21.0'));
        $url = sprintf('https://graph.facebook.com/%s/%s', $version, rawurlencode(trim($mediaId)));

        try {
            $response = Http::timeout(20)
                ->withToken($auth['token'])
                ->acceptJson()
                ->get($url);

            $json = $response->json();
            if (! is_array($json)) {
                $json = [];
            }

            if (! $response->successful()) {
                $error = data_get($json, 'error.message')
                    ?? 'Media no longer available from WhatsApp.';

                return [
                    'ok' => false,
                    'error' => (string) $error,
                    'status' => $response->status(),
                ];
            }

            $downloadUrl = data_get($json, 'url');
            if (! is_string($downloadUrl) || $downloadUrl === '') {
                return ['ok' => false, 'error' => 'Media no longer available from WhatsApp.'];
            }

            return [
                'ok' => true,
                'url' => $downloadUrl,
                'mime' => is_string(data_get($json, 'mime_type')) ? data_get($json, 'mime_type') : null,
                'status' => $response->status(),
            ];
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * @return array{ok: bool, message_id?: string|null, response?: array<string, mixed>, error?: string, status?: int}
     */
    protected function sendMediaMessage(
        WhatsAppConnection $connection,
        string $toE164,
        string $type,
        string $mediaId,
        ?string $caption,
        ?string $filename
    ): array {
        $to = PhoneNumber::toWhatsAppDigits($toE164);
        if (! PhoneNumber::isValidWhatsAppDigits($to)) {
            return ['ok' => false, 'error' => 'Invalid recipient phone number.'];
        }

        $mediaId = trim($mediaId);
        if ($mediaId === '') {
            return ['ok' => false, 'error' => 'Media id is required.'];
        }

        $object = ['id' => $mediaId];
        if ($caption !== null && trim($caption) !== '' && in_array($type, ['image', 'video', 'document'], true)) {
            $object['caption'] = trim($caption);
        }
        if ($filename !== null && trim($filename) !== '' && $type === 'document') {
            $object['filename'] = trim($filename);
        }

        return $this->postMessage($connection, [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $to,
            'type' => $type,
            $type => $object,
        ]);
    }

    /**
     * @return array{ok: bool, token?: string, phone_number_id?: string, error?: string}
     */
    protected function authContext(WhatsAppConnection $connection): array
    {
        $token = $connection->getDecryptedAccessToken();
        if (! is_string($token) || trim($token) === '') {
            return ['ok' => false, 'error' => 'WhatsApp access token is missing. Re-save the connection.'];
        }

        $phoneNumberId = trim((string) $connection->phone_number_id);
        if ($phoneNumberId === '') {
            return ['ok' => false, 'error' => 'WhatsApp phone number ID is missing.'];
        }

        return [
            'ok' => true,
            'token' => trim($token),
            'phone_number_id' => $phoneNumberId,
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{ok: bool, message_id?: string|null, response?: array<string, mixed>, error?: string, status?: int}
     */
    protected function postMessage(WhatsAppConnection $connection, array $payload): array
    {
        $auth = $this->authContext($connection);
        if (! ($auth['ok'] ?? false)) {
            return ['ok' => false, 'error' => (string) ($auth['error'] ?? 'Auth failed.')];
        }

        $version = trim((string) config('whatsapp.graph_version', 'v21.0'));
        $url = sprintf('https://graph.facebook.com/%s/%s/messages', $version, $auth['phone_number_id']);

        try {
            $response = Http::timeout(20)
                ->withToken($auth['token'])
                ->acceptJson()
                ->asJson()
                ->post($url, $payload);

            $json = $response->json();
            if (! is_array($json)) {
                $json = ['raw' => $response->body()];
            }

            if (! $response->successful()) {
                $error = data_get($json, 'error.message')
                    ?? data_get($json, 'error.error_user_msg')
                    ?? ('Meta API error HTTP '.$response->status());

                Log::warning('whatsapp.cloud_api_failed', [
                    'connection_id' => $connection->id,
                    'company_id' => $connection->company_id,
                    'status' => $response->status(),
                    'error' => $error,
                ]);

                return [
                    'ok' => false,
                    'error' => (string) $error,
                    'status' => $response->status(),
                    'response' => $json,
                ];
            }

            $messageId = data_get($json, 'messages.0.id');

            return [
                'ok' => true,
                'message_id' => is_string($messageId) ? $messageId : null,
                'response' => $json,
                'status' => $response->status(),
            ];
        } catch (\Throwable $e) {
            Log::error('whatsapp.cloud_api_exception', [
                'connection_id' => $connection->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'ok' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * @throws RuntimeException
     */
    public function assertOk(array $result): array
    {
        if (! ($result['ok'] ?? false)) {
            throw new RuntimeException((string) ($result['error'] ?? 'WhatsApp send failed.'));
        }

        return $result;
    }
}
