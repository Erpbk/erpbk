<?php

namespace App\Http\Controllers;

use App\Models\RiderInvoiceTemplate;
use App\Support\CompanyContext;
use Flash;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RiderInvoiceTemplateSettingsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    protected function authorizeManage(): void
    {
        $user = auth()->user();
        if (! $user || (! $user->can('gn_settings') && ! $user->can('riderinvoice_edit'))) {
            abort(403, 'Unauthorized action.');
        }
    }

    public function index()
    {
        $this->authorizeManage();

        $templates = RiderInvoiceTemplate::query()
            ->orderByDesc('is_default')
            ->orderBy('display_order')
            ->orderBy('template_name')
            ->get();

        $layouts = RiderInvoiceTemplate::LAYOUTS;

        return view('settings.rider_invoice_templates.index', compact('templates', 'layouts'));
    }

    public function store(Request $request)
    {
        $this->authorizeManage();

        $data = $this->validated($request);
        $template = RiderInvoiceTemplate::create(array_merge($data, [
            'company_id' => CompanyContext::id(),
            'display_order' => (int) (RiderInvoiceTemplate::max('display_order') ?? 0) + 1,
        ]));

        if ($request->boolean('is_default')) {
            $template->setAsDefault();
        }

        Flash::success('Rider invoice template created.');

        return redirect()->route('settings-panel.rider-invoice-templates.index', [
            'company_slug' => $request->route('company_slug'),
        ]);
    }

    public function update(Request $request, string $company_slug, int $id)
    {
        $this->authorizeManage();

        $template = RiderInvoiceTemplate::findOrFail($id);
        $data = $this->validated($request, $template->id);
        $template->fill($data);
        $template->save();

        if ($request->boolean('is_default')) {
            $template->setAsDefault();
        } elseif ($template->is_default && ! $request->boolean('is_default')) {
            $template->is_default = false;
            $template->save();
        }

        Flash::success('Rider invoice template updated.');

        return redirect()->route('settings-panel.rider-invoice-templates.index', [
            'company_slug' => $company_slug,
        ]);
    }

    public function destroy(Request $request, string $company_slug, int $id)
    {
        $this->authorizeManage();

        $template = RiderInvoiceTemplate::findOrFail($id);

        if ($template->invoices()->exists()) {
            Flash::error('Cannot delete a template that is assigned to invoices.');

            return redirect()->back();
        }

        $wasDefault = $template->is_default;
        $template->delete();

        if ($wasDefault) {
            $next = RiderInvoiceTemplate::query()->orderBy('display_order')->orderBy('id')->first();
            if ($next) {
                $next->setAsDefault();
            }
        }

        Flash::success('Rider invoice template deleted.');

        return redirect()->route('settings-panel.rider-invoice-templates.index', [
            'company_slug' => $company_slug,
        ]);
    }

    public function setDefault(Request $request, string $company_slug, int $id)
    {
        $this->authorizeManage();

        RiderInvoiceTemplate::findOrFail($id)->setAsDefault();
        Flash::success('Default template updated.');

        return redirect()->route('settings-panel.rider-invoice-templates.index', [
            'company_slug' => $company_slug,
        ]);
    }

    public function toggleStatus(Request $request, string $company_slug, int $id)
    {
        $this->authorizeManage();

        $template = RiderInvoiceTemplate::findOrFail($id);
        $template->status = ! $template->status;
        $template->save();

        Flash::success('Template status updated.');

        return redirect()->route('settings-panel.rider-invoice-templates.index', [
            'company_slug' => $company_slug,
        ]);
    }

    protected function validated(Request $request, ?int $ignoreId = null): array
    {
        $layoutKeys = array_keys(RiderInvoiceTemplate::LAYOUTS);
        $companyId = CompanyContext::id();

        return $request->validate([
            'template_name' => [
                'required',
                'string',
                'max:120',
                Rule::unique('rider_invoice_templates', 'template_name')
                    ->ignore($ignoreId)
                    ->where(fn ($q) => $q->where('company_id', $companyId)),
            ],
            'layout_key' => ['required', 'string', Rule::in($layoutKeys)],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_default' => ['nullable', 'boolean'],
            'status' => ['nullable', 'boolean'],
        ], [], [
            'template_name' => 'template name',
            'layout_key' => 'layout style',
        ]);
    }
}
