<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WhatsAppConnection;
use App\Models\WhatsAppTemplate;
use App\Services\WhatsApp\WhatsAppTemplateBindingService;
use App\Services\WhatsApp\WhatsAppTemplateInjector;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AdminWhatsAppTemplatesController extends Controller
{
    public function __construct(
        private readonly WhatsAppTemplateBindingService $bindings,
        private readonly WhatsAppTemplateInjector $injector
    ) {
    }

    public function index(Request $request)
    {
        $query = WhatsAppTemplate::query()->orderBy('name')->orderBy('language');

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $templates = $query->paginate(20)->withQueryString();
        $bindingRows = $this->bindings->listingForAdmin();

        return view('admin.whatsapp_templates.index', compact('templates', 'bindingRows'));
    }

    public function create()
    {
        return view('admin.whatsapp_templates.create', [
            'injectionHints' => $this->injectionHints(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateTemplate($request);

        try {
            WhatsAppTemplate::query()->create([
                'label' => $validated['label'] ?? null,
                'name' => trim($validated['name']),
                'language' => $validated['language'] ?: 'en',
                'category' => $validated['category'] ?? null,
                'param_style' => $validated['param_style'],
                'status' => $validated['status'],
                'body_preview' => $validated['body_preview'] ?? null,
            ]);
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', 'Error: '.$e->getMessage());
        }

        return redirect()
            ->route('admin.whatsapp-templates.index')
            ->with('success', __('WhatsApp template created. Add injection mapping in config/whatsapp_templates.php if needed.'));
    }

    public function edit(WhatsAppTemplate $whatsapp_template)
    {
        $injection = $this->injector->injectionFor(
            $whatsapp_template->name,
            $whatsapp_template->language ?: 'en'
        );

        return view('admin.whatsapp_templates.edit', [
            'template' => $whatsapp_template,
            'injection' => $injection,
            'injectionHints' => $this->injectionHints(),
            'bindingRows' => $this->bindings->listingForAdmin(),
            'numbers' => WhatsAppConnection::query()->active()->orderBy('label')->get(),
        ]);
    }

    public function update(Request $request, WhatsAppTemplate $whatsapp_template)
    {
        $validated = $this->validateTemplate($request, $whatsapp_template);

        try {
            $whatsapp_template->update([
                'label' => $validated['label'] ?? null,
                'name' => trim($validated['name']),
                'language' => $validated['language'] ?: 'en',
                'category' => $validated['category'] ?? null,
                'param_style' => $validated['param_style'],
                'status' => $validated['status'],
                'body_preview' => $validated['body_preview'] ?? null,
            ]);
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', 'Error: '.$e->getMessage());
        }

        return redirect()
            ->route('admin.whatsapp-templates.index')
            ->with('success', __('WhatsApp template updated successfully.'));
    }

    public function destroy(WhatsAppTemplate $whatsapp_template)
    {
        try {
            DB::transaction(function () use ($whatsapp_template) {
                DB::table('whatsapp_template_bindings')
                    ->where('whatsapp_template_id', $whatsapp_template->id)
                    ->delete();
                $whatsapp_template->variables()->delete();
                $whatsapp_template->delete();
            });
        } catch (\Throwable $e) {
            return back()->with('error', 'Error: '.$e->getMessage());
        }

        return redirect()
            ->route('admin.whatsapp-templates.index')
            ->with('success', __('WhatsApp template deleted successfully.'));
    }

    public function updateBindings(Request $request)
    {
        $triggers = array_keys(config('whatsapp_templates.triggers', []));
        $validated = $request->validate([
            'bindings' => 'nullable|array',
            'bindings.*.template_id' => ['nullable', 'integer', Rule::exists('whatsapp_templates', 'id')],
            'bindings.*.connection_id' => ['nullable', 'integer', Rule::exists('whatsapp_connections', 'id')],
        ]);

        $rows = is_array($validated['bindings'] ?? null) ? $validated['bindings'] : [];
        foreach ($triggers as $triggerKey) {
            if (! array_key_exists($triggerKey, $rows)) {
                continue;
            }
            $row = is_array($rows[$triggerKey]) ? $rows[$triggerKey] : [];
            $templateId = isset($row['template_id']) && $row['template_id'] !== ''
                ? (int) $row['template_id']
                : null;
            $connectionId = isset($row['connection_id']) && $row['connection_id'] !== ''
                ? (int) $row['connection_id']
                : null;
            $this->bindings->upsert($triggerKey, $templateId ?: null, $connectionId ?: null);
        }

        return redirect()
            ->route('admin.whatsapp-templates.index')
            ->with('success', __('Template bindings saved.'));
    }

    private function validateTemplate(Request $request, ?WhatsAppTemplate $existing = null): array
    {
        $uniqueName = Rule::unique('whatsapp_templates', 'name')
            ->where(fn ($q) => $q->where('language', $request->input('language', 'en')));
        if ($existing) {
            $uniqueName = $uniqueName->ignore($existing->id);
        }

        return $request->validate([
            'label' => 'nullable|string|max:255',
            'name' => ['required', 'string', 'max:128', 'regex:/^[a-z0-9_]+$/', $uniqueName],
            'language' => 'required|string|max:16',
            'category' => 'nullable|string|max:32',
            'param_style' => 'required|in:positional,named',
            'status' => 'required|in:active,inactive',
            'body_preview' => 'nullable|string|max:2000',
        ], [
            'name.regex' => 'Template name must be lowercase letters, numbers, and underscores (Meta format).',
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function injectionHints(): array
    {
        $out = [];
        foreach (config('whatsapp_templates.injections', []) as $name => $row) {
            if (! is_array($row)) {
                continue;
            }
            $vars = collect($row['variables'] ?? [])
                ->map(fn ($v) => is_array($v) ? (($v['parameter_name'] ?? '').'→'.($v['inject'] ?? '')) : '')
                ->filter()
                ->implode(', ');
            $out[$name] = $vars;
        }

        return $out;
    }
}
