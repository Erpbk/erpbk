<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\WhatsAppConnection;
use App\Services\WhatsApp\WhatsAppCloudApiClient;
use App\Services\WhatsApp\WhatsAppConnectionService;
use App\Support\PhoneNumber;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminWhatsAppNumbersController extends Controller
{
    public function __construct(
        private readonly WhatsAppConnectionService $connections
    ) {
    }

    public function index(Request $request)
    {
        $query = WhatsAppConnection::query()->with('company:id,name')->orderByDesc('id');

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->input('assignment') === 'default') {
            $query->where('is_default', true);
        } elseif ($request->input('assignment') === 'company') {
            $query->whereNotNull('company_id')->where('is_default', false);
        } elseif ($request->input('assignment') === 'unassigned') {
            $query->whereNull('company_id')->where('is_default', false);
        }

        $numbers = $query->paginate(20)->withQueryString();
        $webhookUrl = url('/webhooks/meta/whatsapp');

        return view('admin.whatsapp_numbers.index', compact('numbers', 'webhookUrl'));
    }

    public function create()
    {
        $companies = $this->approvedCompanies();

        return view('admin.whatsapp_numbers.create', compact('companies'));
    }

    public function store(Request $request)
    {
        $validated = $this->validateNumber($request);

        try {
            $number = new WhatsAppConnection();
            $number->label = $validated['label'] ?? null;
            $number->phone_number_id = trim($validated['phone_number_id']);
            $number->display_phone_number = $validated['display_phone_number'] ?? null;
            $number->waba_id = $validated['waba_id'] ?? null;
            $number->access_token = $validated['access_token'];
            $number->status = $validated['status'];
            $number->save();

            $this->connections->applyAssignment($number, [
                'is_default' => ($validated['assignment'] ?? '') === 'default',
                'company_id' => ($validated['assignment'] ?? '') === 'company'
                    ? ($validated['company_id'] ?? null)
                    : null,
            ]);
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', 'Error: '.$e->getMessage());
        }

        return redirect()
            ->route('admin.whatsapp-numbers.index')
            ->with('success', __('WhatsApp number created successfully.'));
    }

    public function edit(WhatsAppConnection $whatsapp_number)
    {
        $companies = $this->approvedCompanies();
        $number = $whatsapp_number;

        return view('admin.whatsapp_numbers.edit', compact('number', 'companies'));
    }

    public function update(Request $request, WhatsAppConnection $whatsapp_number)
    {
        $validated = $this->validateNumber($request, $whatsapp_number);

        try {
            $whatsapp_number->label = $validated['label'] ?? null;
            $whatsapp_number->phone_number_id = trim($validated['phone_number_id']);
            $whatsapp_number->display_phone_number = $validated['display_phone_number'] ?? null;
            $whatsapp_number->waba_id = $validated['waba_id'] ?? null;
            if (! empty($validated['access_token'])) {
                $whatsapp_number->access_token = $validated['access_token'];
            }
            $whatsapp_number->status = $validated['status'];
            $whatsapp_number->save();

            $this->connections->applyAssignment($whatsapp_number, [
                'is_default' => ($validated['assignment'] ?? '') === 'default',
                'company_id' => ($validated['assignment'] ?? '') === 'company'
                    ? ($validated['company_id'] ?? null)
                    : null,
            ]);
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', 'Error: '.$e->getMessage());
        }

        return redirect()
            ->route('admin.whatsapp-numbers.index')
            ->with('success', __('WhatsApp number updated successfully.'));
    }

    public function destroy(WhatsAppConnection $whatsapp_number)
    {
        try {
            $whatsapp_number->users()->detach();
            $whatsapp_number->delete();
        } catch (\Throwable $e) {
            return back()->with('error', 'Error: '.$e->getMessage());
        }

        return redirect()
            ->route('admin.whatsapp-numbers.index')
            ->with('success', __('WhatsApp number deleted successfully.'));
    }

    public function testTemplate(Request $request, WhatsAppConnection $whatsapp_number, WhatsAppCloudApiClient $client)
    {
        $validated = $request->validate([
            'to_phone' => 'required|string|max:40',
            'template_name' => 'required|string|max:128',
            'template_language' => 'nullable|string|max:16',
        ]);

        $to = PhoneNumber::toWhatsAppDigits($validated['to_phone']);
        if (! PhoneNumber::isValidWhatsAppDigits($to)) {
            return back()->with('error', __('Invalid recipient phone number.'));
        }

        if (! $whatsapp_number->isActive()) {
            return back()->with('error', __('Number must be active to send a test.'));
        }

        $result = $client->sendTemplate(
            $whatsapp_number,
            $to,
            $validated['template_name'],
            $validated['template_language'] ?: 'en'
        );

        if ($result['ok'] ?? false) {
            return back()->with('success', __('Test template sent. Message ID: :id', [
                'id' => $result['message_id'] ?? 'n/a',
            ]));
        }

        return back()->with('error', __('Test send failed: :error', [
            'error' => $result['error'] ?? 'Unknown error',
        ]));
    }

    /**
     * @return \Illuminate\Support\Collection<int, Company>
     */
    private function approvedCompanies()
    {
        return Company::query()
            ->where('status', 'approved')
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);
    }

    private function validateNumber(Request $request, ?WhatsAppConnection $existing = null): array
    {
        $uniquePhone = Rule::unique('whatsapp_connections', 'phone_number_id');
        if ($existing) {
            $uniquePhone = $uniquePhone->ignore($existing->id);
        }

        return $request->validate([
            'label' => 'nullable|string|max:255',
            'phone_number_id' => ['required', 'string', 'max:64', $uniquePhone],
            'display_phone_number' => 'nullable|string|max:40',
            'waba_id' => 'nullable|string|max:64',
            'access_token' => ($existing ? 'nullable' : 'required').'|string|min:20',
            'status' => 'required|in:active,inactive',
            'assignment' => 'required|in:company,default',
            'company_id' => [
                'nullable',
                'required_if:assignment,company',
                'integer',
                Rule::exists('companies', 'id')->where(fn ($q) => $q->where('status', 'approved')),
            ],
        ], [
            'company_id.required_if' => 'Select a company when assignment is Company.',
        ]);
    }
}
