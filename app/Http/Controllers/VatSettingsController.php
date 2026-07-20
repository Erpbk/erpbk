<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\SavesModuleDisplayLabel;
use App\Http\Controllers\Concerns\SavesModuleMenuIcons;
use App\Support\GlobalAccounts;
use App\Models\Accounts;
use App\Models\Settings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class VatSettingsController extends Controller
{
    use SavesModuleDisplayLabel;
    use SavesModuleMenuIcons;
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:vat_view')->only('index');
        $this->middleware('permission:vat_create')->only('store', 'storeQuarter');
        $this->middleware('permission:vat_edit')->only('store', 'storeQuarter');
        $this->middleware('permission:vat_delete')->only('deleteQuarter');
    }

    /**
     * Keys stored in settings table for VAT configuration.
     */
    protected static function vatKeys(): array
    {
        return ['vat_number', 'vat_percentage', 'vat_enabled', 'vat_input_account_id', 'vat_output_account_id'];
    }

    /**
     * Keys for VAT quarter start months (1-12; each quarter = 3 consecutive months, wrapping Dec→Jan).
     */
    public static function quarterKeys(): array
    {
        return ['vat_quarter_1_start', 'vat_quarter_2_start', 'vat_quarter_3_start', 'vat_quarter_4_start'];
    }

    public static function quarterNameKeys(): array
    {
        return ['vat_quarter_1_name', 'vat_quarter_2_name', 'vat_quarter_3_name', 'vat_quarter_4_name'];
    }

    /**
     * Get quarter start months from settings (null = empty slot). Start can be 1-12.
     */
    protected function getQuarterStarts(): array
    {
        $keys = self::quarterKeys();
        $rows = Settings::whereIn('name', $keys)->pluck('value', 'name');
        $out = [];
        foreach ($keys as $key) {
            $val = $rows[$key] ?? null;
            $out[] = ($val !== null && $val !== '' && (int) $val >= 1 && (int) $val <= 12) ? (int) $val : null;
        }
        return $out;
    }

    /**
     * Return the 3 month numbers for a quarter starting at $start (1-12). Wraps: 11→[11,12,1], 12→[12,1,2].
     */
    public static function quarterMonthsForStart(int $start): array
    {
        if ($start >= 1 && $start <= 10) {
            return [$start, $start + 1, $start + 2];
        }
        if ($start === 11) {
            return [11, 12, 1];
        }
        if ($start === 12) {
            return [12, 1, 2];
        }
        return [];
    }

    /**
     * Get quarter custom names from settings (empty = use auto name from months).
     */
    protected function getQuarterNames(): array
    {
        $keys = self::quarterNameKeys();
        $rows = Settings::whereIn('name', $keys)->pluck('value', 'name');
        $out = [];
        foreach ($keys as $key) {
            $out[] = trim($rows[$key] ?? '') ?: null;
        }
        return $out;
    }

    /**
     * Get quarters for dropdown: [ slot => label ]. Slot is 1-based. Uses Settings (static).
     */
    public static function getQuartersForDropdown(): array
    {
        $keys = self::quarterKeys();
        $nameKeys = self::quarterNameKeys();
        $rows = Settings::whereIn('name', array_merge($keys, $nameKeys))->pluck('value', 'name');
        $monthNames = [
            1 => 'January',
            2 => 'February',
            3 => 'March',
            4 => 'April',
            5 => 'May',
            6 => 'June',
            7 => 'July',
            8 => 'August',
            9 => 'September',
            10 => 'October',
            11 => 'November',
            12 => 'December',
        ];
        $out = [];
        foreach ($keys as $i => $key) {
            $val = $rows[$key] ?? null;
            $start = ($val !== null && $val !== '' && (int) $val >= 1 && (int) $val <= 12) ? (int) $val : null;
            if ($start === null) {
                continue;
            }
            $monthsInQ = self::quarterMonthsForStart($start);
            $autoName = implode(' – ', array_map(function ($m) use ($monthNames) {
                return $monthNames[$m] ?? '';
            }, $monthsInQ));
            $nameKey = $nameKeys[$i];
            $customName = trim($rows[$nameKey] ?? '') ?: null;
            $label = $customName ?: $autoName;
            $out[$i + 1] = $label;
        }
        return $out;
    }

    /**
     * Get quarter label for a billing_month date (Y-m-d or Carbon). Returns '-' if no quarter contains that month.
     */
    public static function getQuarterLabelForBillingMonth($billingMonth): string
    {
        $month = $billingMonth instanceof \Carbon\Carbon
            ? (int) $billingMonth->format('n')
            : (int) date('n', strtotime($billingMonth));
        $quarters = self::getQuartersForDropdown();
        $keys = self::quarterKeys();
        $rows = Settings::whereIn('name', $keys)->pluck('value', 'name');
        foreach ($keys as $i => $key) {
            $val = $rows[$key] ?? null;
            $start = ($val !== null && $val !== '' && (int) $val >= 1 && (int) $val <= 12) ? (int) $val : null;
            if ($start === null) {
                continue;
            }
            if (in_array($month, self::quarterMonthsForStart($start), true)) {
                return $quarters[$i + 1] ?? '-';
            }
        }
        return '-';
    }

    /**
     * Get current VAT settings (from DB, no cache) for the form.
     */
    protected function getVatSettings(): array
    {
        $keys = self::vatKeys();
        $rows = Settings::whereIn('name', $keys)->pluck('value', 'name');
        $out = [];
        foreach ($keys as $key) {
            $out[$key] = $rows[$key] ?? '';
        }
        $out['vat_enabled'] = ($out['vat_enabled'] ?? '1') === '1' ? '1' : '0';
        return $out;
    }

    /**
     * Display the VAT settings page.
     */
    public function index()
    {
        $vat = $this->getVatSettings();
        $moduleLabel = Settings::getMenuLabel('vat_settings');
        $quarterStarts = $this->getQuarterStarts();
        $quarterNames = $this->getQuarterNames();
        $monthNames = [
            1 => 'January',
            2 => 'February',
            3 => 'March',
            4 => 'April',
            5 => 'May',
            6 => 'June',
            7 => 'July',
            8 => 'August',
            9 => 'September',
            10 => 'October',
            11 => 'November',
            12 => 'December',
        ];
        return view('settings.vat_settings.index', compact('vat', 'moduleLabel', 'quarterStarts', 'quarterNames', 'monthNames'));
    }

    /**
     * Save the display name for this module (settings panel sidebar).
     */
    public function storeModuleLabel(Request $request)
    {
        $this->saveModuleDisplayLabel($request, 'vat');

        return redirect()->route('settings-panel.vat-settings.index', [
            'company_slug' => $request->route('company_slug') ?? session('company_slug'),
        ])->with('success', 'Menu labels updated.');
    }

    public function storeModuleIcon(Request $request)
    {
        $this->saveModuleMenuIcons($request, 'vat');

        return redirect()->route('settings-panel.vat-settings.index', [
            'company_slug' => $request->route('company_slug') ?? session('company_slug'),
        ])->with('success', 'Menu icons updated.');
    }

    /**
     * Save VAT settings to the settings table and clear settings cache.
     */
    public function store(Request $request)
    {
        $request->validate([
            'vat_number'             => ['nullable', 'string', 'max:100'],
            'vat_percentage'         => ['nullable', 'numeric', 'min:0', 'max:100'],
            'vat_enabled'            => ['nullable', 'in:0,1'],
            'vat_input_account_id'   => ['nullable', 'integer', 'exists:accounts,id'],
            'vat_output_account_id'  => ['nullable', 'integer', 'exists:accounts,id'],
        ]);

        $vatNumber = $request->input('vat_number', '');
        $vatPercentage = $request->input('vat_percentage', '');
        $vatEnabled = $request->has('vat_enabled') ? '1' : '0';
        $vatInputAccountId = $request->input('vat_input_account_id', '');
        $vatOutputAccountId = $request->input('vat_output_account_id', '');

        Settings::updateOrCreate(['name' => 'vat_number'], ['value' => $vatNumber]);
        Settings::updateOrCreate(['name' => 'vat_percentage'], ['value' => (string) $vatPercentage]);
        Settings::updateOrCreate(['name' => 'vat_enabled'], ['value' => $vatEnabled]);
        Settings::updateOrCreate(['name' => 'vat_input_account_id'], ['value' => (string) $vatInputAccountId]);
        Settings::updateOrCreate(['name' => 'vat_output_account_id'], ['value' => (string) $vatOutputAccountId]);

        Cache::forget('settings');

        return redirect()
            ->route('settings-panel.vat-settings.index')
            ->with('success', 'VAT settings saved successfully.');
    }

    /**
     * Add one VAT quarter via modal (start month 1-12; next two months included, wrapping Dec→Jan). Optional custom name.
     */
    public function storeQuarter(Request $request)
    {
        $request->validate([
            'start_month'   => ['required', 'integer', 'min:1', 'max:12'],
            'quarter_name'  => ['nullable', 'string', 'max:100'],
        ]);
        $start = (int) $request->input('start_month');
        $customName = trim($request->input('quarter_name', '') ?: '');
        $quarterStarts = $this->getQuarterStarts();
        $usedMonths = [];
        foreach ($quarterStarts as $s) {
            if ($s !== null) {
                $usedMonths = array_merge($usedMonths, self::quarterMonthsForStart($s));
            }
        }
        $newMonths = self::quarterMonthsForStart($start);
        if (array_intersect($usedMonths, $newMonths)) {
            return redirect()
                ->to(route('settings-panel.vat-settings.index') . '?tab=quarters')
                ->withInput()
                ->withErrors(['start_month' => 'One or more of these months are already used in another quarter.']);
        }
        $slot = null;
        foreach ($quarterStarts as $i => $s) {
            if ($s === null) {
                $slot = $i;
                break;
            }
        }
        if ($slot === null) {
            return redirect()
                ->to(route('settings-panel.vat-settings.index') . '?tab=quarters')
                ->withErrors(['start_month' => 'Maximum 4 quarters allowed. Remove one to add another.']);
        }
        $keys = self::quarterKeys();
        $nameKeys = self::quarterNameKeys();
        Settings::updateOrCreate(['name' => $keys[$slot]], ['value' => (string) $start]);
        Settings::updateOrCreate(['name' => $nameKeys[$slot]], ['value' => $customName]);
        Cache::forget('settings');
        return redirect()
            ->to(route('settings-panel.vat-settings.index') . '?tab=quarters')
            ->with('success', 'VAT quarter added successfully.');
    }

    /**
     * Get VAT account IDs for the VAT Ledger (combined entries). Defaults to 1023 and 1025 if not set in settings.
     *
     * @return int[] Non-empty array of account IDs to combine (1 or 2 elements).
     */
    public static function getVatAccountIds(): array
    {
        return [\App\Support\GlobalAccounts::id('VAT_PURCHASE_ACCOUNT'), \App\Support\GlobalAccounts::id('VAT_ON_SALES')];
    }

    /**
     * Remove a VAT quarter by slot (1-4).
     */
    public function deleteQuarter(Request $request, $company_slug ,int $slot)
    {
        if ($slot < 1 || $slot > 4) {
            return redirect()->to(route('settings-panel.vat-settings.index') . '?tab=quarters')->with('error', 'Invalid quarter.');
        }
        $keys = self::quarterKeys();
        $nameKeys = self::quarterNameKeys();
        $idx = $slot - 1;
        Settings::where('name', $keys[$idx])->delete();
        Settings::where('name', $nameKeys[$idx])->delete();
        Cache::forget('settings');
        return redirect()
            ->to(route('settings-panel.vat-settings.index') . '?tab=quarters')
            ->with('success', 'VAT quarter removed.');
    }
}
