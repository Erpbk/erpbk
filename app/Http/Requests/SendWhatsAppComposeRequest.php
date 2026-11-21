<?php

namespace App\Http\Requests;

use App\Models\WhatsAppConnection;
use App\Services\WhatsApp\WhatsAppConnectionService;
use App\Support\CompanyContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SendWhatsAppComposeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $companyId = CompanyContext::id();

        return [
            'whatsapp_connection_id' => [
                'required',
                'integer',
                Rule::exists('whatsapp_connections', 'id')->where(function ($query) use ($companyId) {
                    $query->where('status', WhatsAppConnection::STATUS_ACTIVE);
                    if ($companyId !== null) {
                        $query->where('company_id', $companyId);
                    }
                }),
            ],
            'to_phone' => 'required|string|max:40',
            'mode' => 'required|in:text,template',
            'body' => 'required_if:mode,text|nullable|string|max:4096',
            'template_name' => 'required_if:mode,template|nullable|string|max:128',
            'template_language' => 'nullable|string|max:16',
            'template_params' => 'nullable|string|max:2000',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $user = $this->user();
            $connectionId = (int) $this->input('whatsapp_connection_id');
            if (! $user || $connectionId <= 0) {
                return;
            }

            $prepared = app(WhatsAppConnectionService::class)->prepareUserConnection($user, $connectionId);
            if (! ($prepared['ready'] ?? false)) {
                $validator->errors()->add(
                    'whatsapp_connection_id',
                    $prepared['message'] ?? 'You cannot use this WhatsApp connection.'
                );
            }
        });
    }
}
