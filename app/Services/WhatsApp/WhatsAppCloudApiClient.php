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
     * @param  array<string, mixed>  $payload
     * @return array{ok: bool, message_id?: string|null, response?: array<string, mixed>, error?: string, status?: int}
     */
    protected function postMessage(WhatsAppConnection $connection, array $payload): array
    {
        $token = $connection->getDecryptedAccessToken();
        if (! is_string($token) || trim($token) === '') {
            return ['ok' => false, 'error' => 'WhatsApp access token is missing. Re-save the connection.'];
        }

        $phoneNumberId = trim((string) $connection->phone_number_id);
        if ($phoneNumberId === '') {
            return ['ok' => false, 'error' => 'WhatsApp phone number ID is missing.'];
        }

        $version = trim((string) config('whatsapp.graph_version', 'v21.0'));
        $url = sprintf('https://graph.facebook.com/%s/%s/messages', $version, $phoneNumberId);

        try {
            $response = Http::timeout(20)
                ->withToken($token)
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
