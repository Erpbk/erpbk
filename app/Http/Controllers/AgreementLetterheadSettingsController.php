<?php

namespace App\Http\Controllers;

use App\Models\AgreementCategory;
use App\Models\AgreementLetterhead;
use App\Models\Settings;
use App\Services\Agreements\AgreementLetterheadLayout;
use App\Services\Agreements\AgreementLetterheadRasterizer;
use App\Support\CompanyContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Laracasts\Flash\Flash;
use RuntimeException;

class AgreementLetterheadSettingsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(string $company_slug)
    {
        $this->authorizeSettings();

        $defaultLabel = config('menu_labels.defaults.agreements', 'Agreements');
        $moduleLabel = Settings::getMenuLabel('agreements') ?: $defaultLabel;
        $letterheads = AgreementLetterhead::query()->orderByDesc('id')->get();

        return view('settings.agreement_settings.index', [
            'pageTitle' => $moduleLabel . ' – Settings',
            'moduleLabel' => $moduleLabel,
            'defaultLabel' => $defaultLabel,
            'settingsHeading' => $moduleLabel . ' Settings',
            'settingsRoutePrefix' => 'settings-panel.module-settings',
            'settingsRouteParams' => ['company_slug' => $company_slug, 'module' => 'agreements'],
            'moduleKey' => 'agreements',
            'letterheads' => $letterheads,
            'activeTab' => request()->query('tab', 'general'),
        ]);
    }

    public function store(Request $request, string $company_slug)
    {
        $this->authorizeSettings();

        $data = $request->validate([
            'name' => 'nullable|string|max:191',
            'letterhead' => 'required|file|mimes:jpg,jpeg,png,webp,pdf|max:10240',
        ]);

        try {
            $path = app(AgreementLetterheadRasterizer::class)->store(
                $request->file('letterhead'),
                (int) CompanyContext::id()
            );
        } catch (RuntimeException $e) {
            throw ValidationException::withMessages([
                'letterhead' => $e->getMessage(),
            ]);
        }

        $fullPath = storage_path('app/public/' . ltrim($path, '/'));
        $suggested = app(AgreementLetterheadLayout::class)->suggestMarginsFromFilesystem(
            is_readable($fullPath) ? $fullPath : null
        );

        $original = (string) $request->file('letterhead')->getClientOriginalName();
        $name = trim((string) ($data['name'] ?? ''));
        if ($name === '') {
            $name = pathinfo($original, PATHINFO_FILENAME) ?: 'Letterhead';
        }

        AgreementLetterhead::create([
            'name' => $name,
            'path' => $path,
            'original_name' => $original !== '' ? $original : null,
            'suggested_margins' => $suggested,
        ]);

        Flash::success('Letterhead uploaded.');

        return redirect()->to($this->settingsUrl($company_slug) . '#tab-letterhead');
    }

    public function update(Request $request, string $company_slug, AgreementLetterhead $letterhead)
    {
        $this->authorizeSettings();

        $data = $request->validate([
            'name' => 'required|string|max:191',
        ]);

        $letterhead->name = $data['name'];
        $letterhead->save();

        Flash::success('Letterhead name saved.');

        return redirect()->to($this->settingsUrl($company_slug) . '#tab-letterhead');
    }

    public function destroy(Request $request, string $company_slug, AgreementLetterhead $letterhead)
    {
        $this->authorizeSettings();

        AgreementCategory::query()
            ->where('letterhead_id', $letterhead->id)
            ->update(['letterhead_id' => null]);

        $path = $letterhead->relativePath();
        $stillUsed = $path !== '' && AgreementLetterhead::query()
            ->where('id', '!=', $letterhead->id)
            ->where('path', $path)
            ->exists();

        if ($path !== '' && ! $stillUsed) {
            Storage::disk('public')->delete($path);
        }

        $letterhead->delete();

        Flash::success('Letterhead removed.');

        return redirect()->to($this->settingsUrl($company_slug) . '#tab-letterhead');
    }

    private function authorizeSettings(): void
    {
        if (! Gate::allows('gn_settings') && ! Gate::allows('agreements_edit') && ! Gate::allows('agreements_view')) {
            abort(403, 'Unauthorized');
        }
    }

    private function settingsUrl(string $company_slug): string
    {
        return route('settings-panel.module-settings.index', [
            'company_slug' => $company_slug,
            'module' => 'agreements',
        ]);
    }
}
