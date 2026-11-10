<?php

namespace App\Services\Company;

use App\Helpers\Currency;
use App\Models\Branch;
use App\Models\Company;
use App\Models\CompanyDocumentType;
use App\Models\CompanyLocationProfile;
use App\Models\Departments;
use App\Models\Files;
use App\Models\Settings;
use App\Support\CompanyGlobalPreferences;
use App\Support\PublicStorageDisk;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CompanyDetailsService
{
    public const ADMIN_SECTIONS = ['cur', 'time', 'fy', 'legal', 'stamp'];

    public const WARN_SECTIONS = ['cur', 'time', 'fy'];

    public const SHARED_BY_DEFAULT = [
        'trn', 'vatp', 'name', 'country', 'web', 'logo', 'cur', 'curic',
        'datef', 'timef', 'hijri', 'tz', 'usertz', 'week', 'fy', 'vatper', 'vatq',
        'ui', 'dir', 'doc', 'arname', 'mail', 'userlang', 'form', 'cttrn', 'rem',
        'stampimg', 'signimg', 'apply', 'apother',
    ];

    public const DOC_KEYS = [
        'tl' => ['tlno', 'tlexp'],
        'mohre' => ['mono', 'moexp'],
        'imm' => ['imno', 'imexp'],
    ];

    public function bootstrap(Company $company): array
    {
        $this->ensureProfiles($company);
        $profiles = CompanyLocationProfile::query()
            ->with('branch')
            ->where('company_id', $company->id)
            ->orderByRaw("FIELD(entity_type, 'ho', 'br', 'co')")
            ->orderBy('id')
            ->get();

        $entities = $profiles->map(fn (CompanyLocationProfile $p) => $this->profileToEntity($p, $company))->values()->all();
        $fieldOrders = $this->loadFieldOrders($company);

        $stampBoot = app(UserStampSignatureService::class)->bootstrapForCompany($company);

        return [
            'company' => [
                'id' => $company->id,
                'name' => $company->name,
                'slug' => $company->slug,
                'logo_url' => $this->logoUrl($company->logo),
            ],
            'entities' => $entities,
            'field_orders' => $fieldOrders,
            'is_admin' => (bool) Auth::user()?->isAdmin(),
            'can_edit' => Auth::user()?->can('settings_company_setting_edit')
                && Auth::user()?->can('settings_company_setting_create'),
            'admin_sections' => self::ADMIN_SECTIONS,
            'warn_sections' => self::WARN_SECTIONS,
            'currency_catalog' => collect(Currency::catalog())->map(fn ($meta, $code) => [
                'code' => $code,
                'name' => $meta['name'] ?? $code,
                'symbol' => $meta['symbol'] ?? $code,
                'label' => $code . ' — ' . ($meta['name'] ?? $code),
            ])->values()->all(),
            'theme' => $this->loadTheme($company),
            'users' => $stampBoot['users'],
            'user_stamps' => $stampBoot['user_stamps'],
            'current_user_id' => $stampBoot['current_user_id'],
            'departments' => $this->departmentsPayload($company),
        ];
    }

    public function departmentsPayload(Company $company): array
    {
        return Departments::query()
            ->when(
                Schema::hasColumn('departments', 'company_id'),
                fn ($q) => $q->where('company_id', $company->id)
            )
            ->orderBy('name')
            ->get(['id', 'name', 'detail', 'status'])
            ->map(fn (Departments $d) => [
                'id' => $d->id,
                'name' => (string) ($d->name ?? ''),
                'detail' => (string) ($d->detail ?? ''),
                'status' => (bool) ($d->status ?? true),
            ])
            ->values()
            ->all();
    }

    public function ensureProfiles(Company $company): void
    {
        $existing = CompanyLocationProfile::query()
            ->where('company_id', $company->id)
            ->get()
            ->keyBy('branch_id');

        $branches = Branch::query()
            ->where('company_id', $company->id)
            ->orderByRaw("FIELD(branch_type, 'headquarters', 'branch', 'warehouse', 'grage')")
            ->orderBy('id')
            ->get();

        if ($branches->isEmpty()) {
            $hoBranch = new Branch([
                'name' => $company->name,
                'code' => Str::upper(Str::substr(preg_replace('/[^A-Za-z0-9]/', '', $company->slug ?: 'HO') ?: 'HO', 0, 5)),
                'contact' => $company->phone,
                'address' => $company->address,
                'city' => $company->city,
                'branch_type' => 'headquarters',
                'is_active' => true,
                'created_by' => Auth::id() ?? 0,
            ]);
            $hoBranch->company_id = $company->id;
            $hoBranch->save();
            $branches = collect([$hoBranch]);
        }

        $hoProfile = null;
        foreach ($branches as $branch) {
            if ($existing->has($branch->id)) {
                $profile = $existing->get($branch->id);
                if ($profile->entity_type === CompanyLocationProfile::TYPE_HEAD_OFFICE) {
                    $hoProfile = $profile;
                }
                continue;
            }

            $type = $branch->branch_type === 'headquarters'
                ? CompanyLocationProfile::TYPE_HEAD_OFFICE
                : CompanyLocationProfile::TYPE_BRANCH;

            $profile = CompanyLocationProfile::create([
                'company_id' => $company->id,
                'branch_id' => $branch->id,
                'parent_id' => null,
                'entity_type' => $type,
                'code' => $branch->code,
                'label' => $branch->name,
                'place' => $branch->city ?: $branch->address,
                'vals' => $this->defaultVals($company, $branch, $type),
                'inh' => [],
                'banks' => [$this->blankBank()],
                'use_ho_bank' => $type === CompanyLocationProfile::TYPE_BRANCH,
                'field_orders' => null,
                'docs_meta' => [],
                'created_by' => Auth::id(),
            ]);

            if ($type === CompanyLocationProfile::TYPE_HEAD_OFFICE) {
                $hoProfile = $profile;
            }
        }

        if (! $hoProfile) {
            $hoProfile = CompanyLocationProfile::query()
                ->where('company_id', $company->id)
                ->where('entity_type', CompanyLocationProfile::TYPE_HEAD_OFFICE)
                ->first();
        }

        if ($hoProfile) {
            CompanyLocationProfile::query()
                ->where('company_id', $company->id)
                ->where('entity_type', CompanyLocationProfile::TYPE_BRANCH)
                ->whereNull('parent_id')
                ->update(['parent_id' => $hoProfile->id]);
        }

        $this->ensureDocumentTypes($company);
    }

    public function createEntity(Company $company, array $data): CompanyLocationProfile
    {
        return DB::transaction(function () use ($company, $data) {
            $this->ensureProfiles($company);

            // Company Details only creates branches. Separate companies are provisioned by super admin.
            $type = CompanyLocationProfile::TYPE_BRANCH;

            $ho = CompanyLocationProfile::query()
                ->where('company_id', $company->id)
                ->where('entity_type', CompanyLocationProfile::TYPE_HEAD_OFFICE)
                ->first();

            $code = Str::upper(Str::substr((string) ($data['code'] ?? 'NEW'), 0, 5));
            $name = trim((string) ($data['name'] ?? ''));
            $place = trim((string) ($data['emirate'] ?? $data['place'] ?? ''));

            $branch = new Branch([
                'name' => $name,
                'code' => $code,
                'contact' => null,
                'address' => $place,
                'city' => $place,
                'parent_branch_id' => $ho?->branch_id,
                'branch_type' => 'branch',
                'is_active' => true,
                'created_by' => Auth::id() ?? 0,
            ]);
            $branch->company_id = $company->id;
            $branch->save();

            $vals = $this->defaultVals($company, $branch, $type);
            $vals['city'] = $place;
            $vals['country'] = $data['country'] ?? 'United Arab Emirates';

            return CompanyLocationProfile::create([
                'company_id' => $company->id,
                'branch_id' => $branch->id,
                'parent_id' => $ho?->id,
                'entity_type' => $type,
                'code' => $code,
                'label' => $name,
                'place' => $place,
                'vals' => $vals,
                'inh' => [],
                'banks' => [$this->blankBank()],
                'use_ho_bank' => true,
                'docs_meta' => [],
                'created_by' => Auth::id(),
            ]);
        });
    }

    public function saveEntity(Company $company, CompanyLocationProfile $profile, array $payload, bool $isAdmin): CompanyLocationProfile
    {
        if ((int) $profile->company_id !== (int) $company->id) {
            abort(404);
        }

        $dirtySections = array_values(array_filter((array) ($payload['dirty_sections'] ?? [])));
        $lockedTouched = array_values(array_intersect($dirtySections, self::ADMIN_SECTIONS));
        if ($lockedTouched !== [] && ! $isAdmin) {
            abort(403, 'Administrator access required for these settings.');
        }

        return DB::transaction(function () use ($company, $profile, $payload, $lockedTouched) {
            $incoming = (array) ($payload['vals'] ?? []);
            $existingVals = (array) $profile->vals;
            foreach (['logo', 'stampimg', 'signimg', 'curimg'] as $imgField) {
                if (! array_key_exists($imgField, $incoming)) {
                    continue;
                }
                $value = $incoming[$imgField];
                // UI may send a public URL; keep stored path unless a new storage path was uploaded.
                if (is_string($value) && (Str::startsWith($value, ['http://', 'https://', 'data:', 'blob:']) || $value === '')) {
                    if (! empty($existingVals[$imgField . '_path'])) {
                        $incoming[$imgField] = $existingVals[$imgField . '_path'];
                    } elseif (! empty($existingVals[$imgField]) && ! Str::startsWith((string) $existingVals[$imgField], ['http://', 'https://', 'data:', 'blob:'])) {
                        $incoming[$imgField] = $existingVals[$imgField];
                    } else {
                        unset($incoming[$imgField]);
                    }
                }
                unset($incoming[$imgField . '_path'], $incoming[$imgField . '_url']);
            }
            $vals = array_merge($existingVals, $incoming);
            unset($vals['logo_path'], $vals['stampimg_path'], $vals['signimg_path'], $vals['curimg_path'], $vals['logo_url'], $vals['stampimg_url'], $vals['signimg_url'], $vals['curimg_url']);
            $inh = (array) ($payload['inh'] ?? $profile->inh ?? []);
            $banks = $this->normalizeBanks((array) ($payload['banks'] ?? $profile->banks ?? []));
            $useHoBank = (bool) ($payload['use_ho_bank'] ?? $profile->use_ho_bank);
            $docsMeta = (array) ($payload['docs_meta'] ?? $profile->docs_meta ?? []);
            $fieldOrders = $payload['field_orders'] ?? null;

            $profile->fill([
                'label' => $payload['label'] ?? $profile->label,
                'place' => $payload['place'] ?? $profile->place,
                'code' => $payload['code'] ?? $profile->code,
                'vals' => $vals,
                'inh' => $inh,
                'banks' => $banks,
                'use_ho_bank' => $useHoBank,
                'docs_meta' => $docsMeta,
                'updated_by' => Auth::id(),
            ]);

            if (is_array($fieldOrders)) {
                $profile->field_orders = $fieldOrders;
                $this->persistFieldOrders($company, $fieldOrders);
            }

            $profile->save();
            $this->syncBranchBasics($profile, $vals);
            $this->syncCompanyBasics($company, $profile, $vals);
            $this->syncCurrencyAndDate($company, $profile, $vals, $lockedTouched);
            $this->syncTheme($company, $payload['theme'] ?? null);

            return $profile->fresh();
        });
    }

    public function deleteEntity(Company $company, CompanyLocationProfile $profile): void
    {
        if ((int) $profile->company_id !== (int) $company->id) {
            abort(404);
        }
        if ($profile->isHeadOffice()) {
            abort(422, 'Head Office cannot be deleted.');
        }

        DB::transaction(function () use ($profile) {
            $branch = $profile->branch;
            $profile->delete();
            if ($branch) {
                $branch->delete();
            }
        });
    }

    public function uploadAsset(Company $company, CompanyLocationProfile $profile, string $field, UploadedFile $file): string
    {
        $dir = 'company-details/' . $company->id . '/' . $profile->id;
        $path = PublicStorageDisk::storeUploadedFile($file, $dir);
        $vals = (array) $profile->vals;
        $old = $vals[$field] ?? null;
        if (is_string($old) && $old !== '' && ! Str::startsWith($old, ['http://', 'https://', 'data:'])) {
            PublicStorageDisk::delete($old);
        }
        $vals[$field] = $path;
        $profile->vals = $vals;
        $profile->save();

        if ($field === 'logo' && $profile->isHeadOffice()) {
            if (! empty($company->logo) && $company->logo !== $path) {
                PublicStorageDisk::delete($company->logo);
            }
            $company->logo = $path;
            $company->save();
            Settings::updateOrCreate(
                ['name' => 'company_logo', 'company_id' => $company->id],
                ['value' => $path, 'company_id' => $company->id]
            );
        }

        return $this->assetPublicUrl($path);
    }

    public function uploadDocument(
        Company $company,
        CompanyLocationProfile $profile,
        UploadedFile $file,
        array $meta
    ): Files {
        $docTypeId = isset($meta['document_type_id']) ? (int) $meta['document_type_id'] : null;
        $docName = trim((string) ($meta['name'] ?? 'Document'));
        $key = $meta['key'] ?? null;
        $number = $meta['number'] ?? null;
        $expiry = $meta['expiry_date'] ?? null;

        if ($key && isset(self::DOC_KEYS[$key])) {
            $vals = (array) $profile->vals;
            if ($number !== null) {
                $vals[self::DOC_KEYS[$key][0]] = $number;
            }
            if ($expiry !== null) {
                $vals[self::DOC_KEYS[$key][1]] = $expiry;
            }
            $profile->vals = $vals;
            $profile->save();
        }

        if (! $docTypeId) {
            $docType = CompanyDocumentType::query()
                ->where('company_id', $company->id)
                ->when($key, fn ($q) => $q->where('key', $key))
                ->when(! $key, fn ($q) => $q->where('name', $docName))
                ->first();
            if (! $docType) {
                $docType = CompanyDocumentType::create([
                    'company_id' => $company->id,
                    'name' => $docName,
                    'key' => $key,
                    'sort_order' => 100,
                    'is_active' => true,
                ]);
            }
            $docTypeId = $docType->id;
        }

        $stored = $file->store('company-documents/' . $company->id, 'public');
        $existing = Files::query()
            ->where('company_id', $company->id)
            ->where('type', 'company')
            ->where('type_id', $profile->id)
            ->where('document_type_id', $docTypeId)
            ->first();

        if ($existing) {
            if ($existing->file_name && Storage::disk('public')->exists($existing->file_name)) {
                Storage::disk('public')->delete($existing->file_name);
            }
            $existing->update([
                'name' => $docName,
                'branch_id' => $profile->branch_id,
                'file_name' => $stored,
                'expiry_date' => $expiry ?: $existing->expiry_date,
                'status' => $this->expiryStatus($expiry ?: $existing->expiry_date),
                'file_type' => $file->getClientOriginalExtension(),
                'field_values' => array_filter([
                    'number' => $number,
                    'key' => $key,
                    'original_name' => $file->getClientOriginalName(),
                ]),
            ]);

            return $existing->fresh();
        }

        return Files::create([
            'company_id' => $company->id,
            'name' => $docName,
            'type' => 'company',
            'type_id' => $profile->id,
            'branch_id' => $profile->branch_id,
            'document_type_id' => $docTypeId,
            'file_name' => $stored,
            'expiry_date' => $expiry,
            'status' => $this->expiryStatus($expiry),
            'file_type' => $file->getClientOriginalExtension(),
            'field_values' => array_filter([
                'number' => $number,
                'key' => $key,
                'original_name' => $file->getClientOriginalName(),
            ]),
        ]);
    }

    public function resolveInheritedValue(CompanyLocationProfile $profile, string $field, ?CompanyLocationProfile $ho = null)
    {
        $ho = $ho ?: CompanyLocationProfile::query()
            ->where('company_id', $profile->company_id)
            ->where('entity_type', CompanyLocationProfile::TYPE_HEAD_OFFICE)
            ->first();

        if (! $profile->isBranch() || ! $ho) {
            return data_get($profile->vals, $field);
        }

        // Row-level inheritance is evaluated in the UI; here we return raw stored value.
        return data_get($profile->vals, $field);
    }

    protected function profileToEntity(CompanyLocationProfile $profile, Company $company): array
    {
        $vals = (array) $profile->vals;
        foreach (['logo', 'stampimg', 'signimg', 'curimg'] as $imgField) {
            if (! empty($vals[$imgField]) && is_string($vals[$imgField]) && ! Str::startsWith($vals[$imgField], ['http://', 'https://', 'data:'])) {
                $vals[$imgField . '_path'] = $vals[$imgField];
                $vals[$imgField] = $this->logoUrl($vals[$imgField]);
            }
        }

        $docs = $this->documentsForProfile($profile, $company);

        $branch = $profile->branch;

        return [
            'id' => $profile->id,
            'branch_id' => $profile->branch_id,
            'parent_id' => $profile->parent_id,
            'type' => $profile->entity_type,
            'code' => $profile->code,
            'label' => $profile->label,
            'place' => $profile->place,
            'active' => (bool) ($branch?->is_active ?? true),
            'branch_type' => (string) ($branch?->branch_type ?? ($profile->entity_type === CompanyLocationProfile::TYPE_HEAD_OFFICE ? 'headquarters' : 'branch')),
            'vals' => $vals,
            'inh' => (array) $profile->inh,
            'banks' => $this->normalizeBanks((array) ($profile->banks ?: [$this->blankBank()])),
            'useHoBank' => (bool) $profile->use_ho_bank,
            'docs' => $docs,
            'field_orders' => $profile->field_orders,
        ];
    }

    protected function documentsForProfile(CompanyLocationProfile $profile, Company $company): array
    {
        $types = CompanyDocumentType::query()
            ->where('company_id', $company->id)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        if ($types->isEmpty()) {
            $this->ensureDocumentTypes($company);
            $types = CompanyDocumentType::query()
                ->where('company_id', $company->id)
                ->orderBy('sort_order')
                ->get();
        }

        $wantedKeys = $profile->isBranch()
            ? ['tl', 'mohre', 'imm', null]
            : null;

        $files = Files::query()
            ->where('company_id', $company->id)
            ->where('type', 'company')
            ->where('type_id', $profile->id)
            ->get()
            ->keyBy('document_type_id');

        $docs = [];
        foreach ($types as $type) {
            if ($profile->isBranch() && $type->key === null && ! in_array($type->name, [
                'Trade License', 'MOHRE Establishment', 'Immigration Establishment Card', 'Tenancy Contract (Ejari)',
            ], true)) {
                continue;
            }

            $file = $files->get($type->id);
            $number = null;
            $expiry = null;
            if ($type->key && isset(self::DOC_KEYS[$type->key])) {
                $number = data_get($profile->vals, self::DOC_KEYS[$type->key][0]);
                $expiry = data_get($profile->vals, self::DOC_KEYS[$type->key][1]);
            } else {
                $number = data_get($file?->field_values, 'number');
                $expiry = optional($file?->expiry_date)?->format('Y-m-d');
            }

            $original = data_get($file?->field_values, 'original_name') ?: ($file?->name);
            $docs[] = [
                'id' => $file?->id,
                'document_type_id' => $type->id,
                'n' => $type->name,
                'key' => $type->key ?: '',
                'no' => $number ?: '',
                'exp' => $expiry ?: '',
                'file' => $original ?: '',
                'url' => $file?->file_name ? $this->logoUrl($file->file_name) : '',
                'kind' => $file && Str::endsWith(Str::lower((string) $file->file_name), '.pdf') ? 'pdf' : 'img',
                'up' => $file?->updated_at?->format('d M Y, h:i A') ?: '',
                'status' => $this->expiryStatusLabel($expiry),
            ];
        }

        // Custom docs stored only in docs_meta
        foreach ((array) $profile->docs_meta as $extra) {
            if (! empty($extra['document_type_id'])) {
                continue;
            }
            $docs[] = array_merge([
                'id' => null,
                'document_type_id' => null,
                'n' => '',
                'key' => '',
                'no' => '',
                'exp' => '',
                'file' => '',
                'url' => '',
                'kind' => 'img',
                'up' => '',
                'status' => 'none',
            ], $extra);
        }

        return $docs;
    }

    protected function ensureDocumentTypes(Company $company): void
    {
        $count = CompanyDocumentType::query()->where('company_id', $company->id)->count();
        if ($count > 0) {
            return;
        }

        foreach (CompanyDocumentType::defaultsForHeadOffice() as $row) {
            CompanyDocumentType::create([
                'company_id' => $company->id,
                'name' => $row['name'],
                'key' => $row['key'],
                'sort_order' => $row['sort_order'],
                'is_active' => true,
            ]);
        }
    }

    protected function defaultVals(Company $company, Branch $branch, string $type): array
    {
        $currency = Currency::code($company->id);
        $prefs = CompanyGlobalPreferences::all($company->id);
        $dateKey = $prefs['date_format'] ?? 'd-m-Y';
        $dateMap = [
            'd-m-Y' => 'DD-MM-YYYY',
            'd/m/Y' => 'DD/MM/YYYY',
            'm/d/Y' => 'MM/DD/YYYY',
            'Y-m-d' => 'YYYY-MM-DD',
            'd M Y' => 'DD MMM YYYY',
        ];

        $base = [
            'trn' => (string) (Settings::query()->where('company_id', $company->id)->where('name', 'vat_number')->value('value') ?? $company->ntn_number ?? ''),
            'vatp' => (string) (Settings::query()->where('company_id', $company->id)->where('name', 'vat_percentage')->value('value') ?? '5'),
            'name' => $type === CompanyLocationProfile::TYPE_HEAD_OFFICE ? $company->name : '',
            'phone' => $type === CompanyLocationProfile::TYPE_HEAD_OFFICE ? (string) $company->phone : (string) $branch->contact,
            'email' => $type === CompanyLocationProfile::TYPE_HEAD_OFFICE ? (string) $company->email : '',
            'wa' => '',
            'phone2' => '',
            'address' => $type === CompanyLocationProfile::TYPE_HEAD_OFFICE ? (string) $company->address : (string) $branch->address,
            'city' => $type === CompanyLocationProfile::TYPE_HEAD_OFFICE ? (string) $company->city : (string) $branch->city,
            'country' => $type === CompanyLocationProfile::TYPE_HEAD_OFFICE ? (string) ($company->country ?: 'United Arab Emirates') : 'United Arab Emirates',
            'web' => (string) (Settings::query()->where('company_id', $company->id)->where('name', 'company_website')->value('value') ?? ''),
            'logo' => $type === CompanyLocationProfile::TYPE_HEAD_OFFICE ? (string) ($company->logo ?? '') : '',
            'cur' => $currency . ' — ' . (Currency::catalog()[$currency]['name'] ?? $currency),
            'curmode' => Currency::iconPath() ? 'c' : 'd',
            'curimg' => (string) (Currency::iconPath() ?? ''),
            'datef' => $dateMap[$dateKey] ?? 'DD-MM-YYYY',
            'timef' => (string) (Settings::query()->where('company_id', $company->id)->where('name', 'time_format')->value('value') ?? '12'),
            'secs' => false,
            'hijri' => 'off',
            'tz' => 'Asia/Dubai',
            'usertz' => false,
            'wstart' => '1',
            'woff' => '5',
            'fy' => '0',
            'vatper' => 'q',
            'vatq' => '0',
            'ui' => 'en',
            'dir' => 'auto',
            'doc' => 'en',
            'arname' => '',
            'mail' => 'same',
            'userlang' => true,
            'tlno' => '',
            'tlauth' => '',
            'tlissue' => '',
            'tlexp' => '',
            'ltype' => 'Limited Liability Company (LLC)',
            'lest' => '',
            'mono' => '',
            'moexp' => '',
            'imno' => '',
            'imexp' => '',
            'cttrn' => '',
            'pobox' => '',
            'remd' => '30 days before',
            'remto' => 'Admins',
            'stampimg' => '',
            'signimg' => '',
            'signame' => '',
            'sigdes' => '',
            'ap4' => false,
            'ap5' => false,
            'ap7' => false,
            'ap8' => true,
            'apm' => $this->defaultMatrix(),
        ];

        return $base;
    }

    protected function defaultMatrix(): array
    {
        $groups = [
            'Cash & Banks', 'Accounts', 'Vouchers', 'Expenses', 'VAT',
            'Employees', 'Riders', 'Recruiters', 'Customers',
            'Bikes', 'Vehicle on rent', 'Fuel Cards', 'Sims', 'RTA Fines', 'RTA Saliks', 'Garages', 'Leasing Companies',
            'Agreements', 'Legal Case', 'Visa Expense', 'License Expense', 'Passport Handover', 'Documents',
            'Supplier', 'Items', 'Assets',
        ];
        $on = [
            'v' => ['Cash & Banks', 'Accounts', 'Vouchers', 'Expenses', 'Supplier', 'Garages', 'Leasing Companies'],
            'i' => ['Customers', 'Accounts', 'VAT', 'Vehicle on rent'],
            'a' => ['Agreements', 'Riders', 'Employees', 'Leasing Companies', 'Vehicle on rent', 'Customers', 'Supplier'],
        ];
        $m = [];
        foreach ($groups as $g) {
            $m[$g] = [
                'v' => in_array($g, $on['v'], true),
                'i' => in_array($g, $on['i'], true),
                'a' => in_array($g, $on['a'], true),
            ];
        }

        return $m;
    }

    public const BANK_DOC_KINDS = ['invoice', 'payment', 'customer_note'];

    /**
     * Same ERP module list as Stamp & Signature apply-matrix.
     *
     * @return list<string>
     */
    public function bankErpModules(): array
    {
        return [
            'Cash & Banks', 'Accounts', 'Vouchers', 'Expenses', 'VAT',
            'Employees', 'Riders', 'Recruiters', 'Customers',
            'Bikes', 'Vehicle on rent', 'Fuel Cards', 'Sims', 'RTA Fines', 'RTA Saliks', 'Garages', 'Leasing Companies',
            'Agreements', 'Legal Case', 'Visa Expense', 'License Expense', 'Passport Handover', 'Documents',
            'Supplier', 'Items', 'Assets',
        ];
    }

    /**
     * @param  array{invoice?:bool,payment?:bool,customer_note?:bool}|null  $seed
     * @return array<string, array{invoice:bool,payment:bool,customer_note:bool}>
     */
    public function defaultBankMatrix(?array $seed = null): array
    {
        $on = [
            'invoice' => ['Customers', 'Accounts', 'VAT', 'Vehicle on rent'],
            'payment' => ['Cash & Banks', 'Accounts', 'Vouchers', 'Expenses', 'Supplier', 'Garages', 'Leasing Companies', 'Customers', 'Riders', 'Employees'],
            'customer_note' => ['Customers'],
        ];
        $use = [
            'invoice' => $seed === null ? true : (bool) ($seed['invoice'] ?? false),
            'payment' => $seed === null ? true : (bool) ($seed['payment'] ?? false),
            'customer_note' => $seed === null ? true : (bool) ($seed['customer_note'] ?? false),
        ];
        // When no seed, use default "on" lists as-is (payment/customer_note still only on their lists).
        if ($seed === null) {
            $use = ['invoice' => true, 'payment' => true, 'customer_note' => true];
        }

        $m = [];
        foreach ($this->bankErpModules() as $mod) {
            $m[$mod] = [
                'invoice' => $use['invoice'] && in_array($mod, $on['invoice'], true),
                'payment' => $use['payment'] && in_array($mod, $on['payment'], true),
                'customer_note' => $use['customer_note'] && in_array($mod, $on['customer_note'], true),
            ];
        }

        return $m;
    }

    /**
     * @param  array<string, array{invoice?:bool,payment?:bool,customer_note?:bool}>  $matrix
     * @return array{invoice:bool,payment:bool,customer_note:bool}
     */
    protected function bankModulesFromMatrix(array $matrix): array
    {
        $mods = ['invoice' => false, 'payment' => false, 'customer_note' => false];
        foreach ($matrix as $row) {
            if (! is_array($row)) {
                continue;
            }
            foreach (self::BANK_DOC_KINDS as $kind) {
                if (! empty($row[$kind])) {
                    $mods[$kind] = true;
                }
            }
        }

        return $mods;
    }

    /**
     * @param  mixed  $src
     * @return array<string, array{invoice:bool,payment:bool,customer_note:bool}>
     */
    public function normalizeBankMatrix(mixed $src, ?array $legacyModules = null, mixed $legacyShow = null): array
    {
        if (is_array($src) && $src !== []) {
            $looksLikeMatrix = false;
            foreach ($src as $row) {
                if (is_array($row) && (array_key_exists('invoice', $row) || array_key_exists('payment', $row) || array_key_exists('customer_note', $row))) {
                    $looksLikeMatrix = true;
                    break;
                }
            }
            if ($looksLikeMatrix) {
                $base = $this->defaultBankMatrix(['invoice' => false, 'payment' => false, 'customer_note' => false]);
                foreach ($base as $mod => $_) {
                    $row = is_array($src[$mod] ?? null) ? $src[$mod] : [];
                    $base[$mod] = [
                        'invoice' => (bool) ($row['invoice'] ?? false),
                        'payment' => (bool) ($row['payment'] ?? false),
                        'customer_note' => (bool) ($row['customer_note'] ?? false),
                    ];
                }

                return $base;
            }
        }

        if (is_array($legacyModules) && (isset($legacyModules['invoice']) || isset($legacyModules['payment']) || isset($legacyModules['customer_note']))) {
            $flat = true;
            foreach ($legacyModules as $v) {
                if (is_array($v)) {
                    $flat = false;
                    break;
                }
            }
            if ($flat) {
                return $this->defaultBankMatrix([
                    'invoice' => (bool) ($legacyModules['invoice'] ?? false),
                    'payment' => (bool) ($legacyModules['payment'] ?? false),
                    'customer_note' => (bool) ($legacyModules['customer_note'] ?? false),
                ]);
            }
        }

        if ($legacyShow !== null) {
            return $this->defaultBankMatrix([
                'invoice' => (bool) $legacyShow,
                'payment' => false,
                'customer_note' => false,
            ]);
        }

        return $this->defaultBankMatrix();
    }

    protected function blankBank(): array
    {
        $matrix = $this->defaultBankMatrix();

        return [
            'bank' => 'Emirates NBD',
            'title' => '',
            'iban' => '',
            'swift' => '',
            'acc' => '',
            'cur' => 'AED',
            'matrix' => $matrix,
            'modules' => $this->bankModulesFromMatrix($matrix),
            'show' => true,
        ];
    }

    /**
     * @param  array<int, mixed>  $banks
     * @return array<int, array<string, mixed>>
     */
    public function normalizeBanks(array $banks): array
    {
        if ($banks === []) {
            return [$this->blankBank()];
        }

        return array_values(array_map(function ($row) {
            $src = is_array($row) ? $row : [];
            $matrix = $this->normalizeBankMatrix(
                $src['matrix'] ?? null,
                isset($src['modules']) && is_array($src['modules']) ? $src['modules'] : null,
                $src['show'] ?? null
            );
            $modules = $this->bankModulesFromMatrix($matrix);

            return [
                'bank' => (string) ($src['bank'] ?? 'Emirates NBD'),
                'title' => (string) ($src['title'] ?? ''),
                'iban' => (string) ($src['iban'] ?? ''),
                'swift' => (string) ($src['swift'] ?? ''),
                'acc' => (string) ($src['acc'] ?? ''),
                'cur' => (string) ($src['cur'] ?? 'AED'),
                'matrix' => $matrix,
                'modules' => $modules,
                'show' => (bool) $modules['invoice'],
            ];
        }, $banks));
    }

    /**
     * Banks configured for a document kind (invoice|payment|customer_note),
     * optionally limited to one ERP module (e.g. Customers).
     *
     * @return array<int, array<string, mixed>>
     */
    public function banksForModule(Company $company, string $module, ?int $branchId = null, ?string $erpModule = null): array
    {
        $docKind = in_array($module, self::BANK_DOC_KINDS, true) ? $module : 'invoice';
        $this->ensureProfiles($company);

        $query = CompanyLocationProfile::query()->where('company_id', $company->id);
        if ($branchId) {
            $profile = (clone $query)->where('branch_id', $branchId)->first();
        } else {
            $profile = (clone $query)
                ->where('entity_type', CompanyLocationProfile::TYPE_HEAD_OFFICE)
                ->first();
        }

        if (! $profile) {
            return [];
        }

        if ($profile->use_ho_bank && $profile->entity_type !== CompanyLocationProfile::TYPE_HEAD_OFFICE) {
            $profile = CompanyLocationProfile::query()
                ->where('company_id', $company->id)
                ->where('entity_type', CompanyLocationProfile::TYPE_HEAD_OFFICE)
                ->first() ?: $profile;
        }

        return array_values(array_filter(
            $this->normalizeBanks((array) ($profile->banks ?: [])),
            function (array $bank) use ($docKind, $erpModule) {
                if ($bank['iban'] === '' && $bank['acc'] === '' && $bank['title'] === '') {
                    return false;
                }
                $matrix = is_array($bank['matrix'] ?? null) ? $bank['matrix'] : [];
                if ($erpModule) {
                    return ! empty($matrix[$erpModule][$docKind]);
                }
                if (! empty($bank['modules'][$docKind])) {
                    return true;
                }
                foreach ($matrix as $row) {
                    if (is_array($row) && ! empty($row[$docKind])) {
                        return true;
                    }
                }

                return false;
            }
        ));
    }

    /**
     * Plain-text block of bank accounts for notes / PDFs.
     */
    public function banksTextForModule(Company $company, string $module, ?int $branchId = null, ?string $erpModule = null): string
    {
        $lines = [];
        foreach ($this->banksForModule($company, $module, $branchId, $erpModule) as $i => $bank) {
            $parts = array_filter([
                $bank['bank'] ?: null,
                $bank['title'] ? 'Title: '.$bank['title'] : null,
                $bank['iban'] ? 'IBAN: '.$bank['iban'] : null,
                $bank['acc'] ? 'Account: '.$bank['acc'] : null,
                $bank['swift'] ? 'SWIFT: '.$bank['swift'] : null,
                $bank['cur'] ? 'Currency: '.$bank['cur'] : null,
            ]);
            if ($parts === []) {
                continue;
            }
            $lines[] = 'Bank account '.($i + 1).': '.implode(' · ', $parts);
        }

        return implode("\n", $lines);
    }

    protected function syncBranchBasics(CompanyLocationProfile $profile, array $vals): void
    {
        $branch = $profile->branch;
        if (! $branch) {
            return;
        }

        $branch->name = $profile->label ?: $branch->name;
        $branch->code = $profile->code ?: $branch->code;
        $branch->contact = $vals['phone'] ?? $branch->contact;
        $branch->address = $vals['address'] ?? $branch->address;
        $branch->city = $vals['city'] ?? $branch->city;
        $branch->updated_by = Auth::id();
        $branch->save();
    }

    protected function syncCompanyBasics(Company $company, CompanyLocationProfile $profile, array $vals): void
    {
        if (! $profile->isHeadOffice()) {
            return;
        }

        if (! empty($vals['name'])) {
            $company->name = $vals['name'];
        }
        if (array_key_exists('email', $vals)) {
            $company->email = $vals['email'] ?: null;
        }
        if (array_key_exists('phone', $vals)) {
            $company->phone = $vals['phone'] ?: null;
        }
        if (array_key_exists('address', $vals)) {
            $company->address = $vals['address'] ?: null;
        }
        if (array_key_exists('city', $vals)) {
            $company->city = $vals['city'] ?: null;
        }
        if (array_key_exists('country', $vals)) {
            $company->country = $vals['country'] ?: null;
        }
        $company->save();

        foreach ([
            'company_name' => $company->name,
            'company_email' => $company->email,
            'company_phone' => $company->phone,
            'company_address' => $company->address,
            'company_city' => $company->city,
            'company_country' => $company->country,
            'vat_number' => $vals['trn'] ?? null,
            'vat_percentage' => $vals['vatp'] ?? null,
            'company_website' => $vals['web'] ?? null,
        ] as $name => $value) {
            if ($value === null) {
                continue;
            }
            Settings::updateOrCreate(
                ['name' => $name, 'company_id' => $company->id],
                ['value' => $value, 'company_id' => $company->id]
            );
        }

        // Reminder configuration stored in Settings (not Reminder module)
        Settings::updateOrCreate(
            ['name' => 'company_details_reminder_days', 'company_id' => $company->id],
            ['value' => (string) ($vals['remd'] ?? '30 days before'), 'company_id' => $company->id]
        );
        Settings::updateOrCreate(
            ['name' => 'company_details_reminder_notify', 'company_id' => $company->id],
            ['value' => (string) ($vals['remto'] ?? 'Admins'), 'company_id' => $company->id]
        );
    }

    protected function syncCurrencyAndDate(Company $company, CompanyLocationProfile $profile, array $vals, array $lockedTouched): void
    {
        if (! $profile->isHeadOffice() && ! $profile->isSeparateCompany()) {
            return;
        }

        if (in_array('cur', $lockedTouched, true) || isset($vals['cur'])) {
            $code = Str::upper(Str::substr((string) ($vals['cur'] ?? 'AED'), 0, 3));
            $catalog = Currency::catalog();
            if (! isset($catalog[$code])) {
                $code = 'AED';
            }
            Settings::updateOrCreate(
                ['name' => 'currency_code', 'company_id' => $company->id],
                ['value' => $code, 'company_id' => $company->id]
            );
            Settings::updateOrCreate(
                ['name' => 'currency_symbol', 'company_id' => $company->id],
                ['value' => $catalog[$code]['symbol'] ?? $code, 'company_id' => $company->id]
            );

            $mode = $vals['curmode'] ?? 'd';
            if ($mode === 'd') {
                $existing = (string) (Settings::query()->where('company_id', $company->id)->where('name', 'currency_icon')->value('value') ?? '');
                if ($existing !== '' && PublicStorageDisk::exists($existing)) {
                    PublicStorageDisk::disk()->delete($existing);
                }
                Settings::updateOrCreate(
                    ['name' => 'currency_icon', 'company_id' => $company->id],
                    ['value' => '', 'company_id' => $company->id]
                );
            } elseif (! empty($vals['curimg']) && is_string($vals['curimg']) && ! Str::startsWith($vals['curimg'], 'data:')) {
                Settings::updateOrCreate(
                    ['name' => 'currency_icon', 'company_id' => $company->id],
                    ['value' => $vals['curimg'], 'company_id' => $company->id]
                );
            }
            Currency::clearCache($company->id);
        }

        if (isset($vals['datef'])) {
            $map = [
                'DD-MM-YYYY' => 'd-m-Y',
                'DD/MM/YYYY' => 'd/m/Y',
                'DD.MM.YYYY' => 'd.m.Y',
                'MM-DD-YYYY' => 'm-d-Y',
                'MM/DD/YYYY' => 'm/d/Y',
                'YYYY-MM-DD' => 'Y-m-d',
                'DD MMM YYYY' => 'd M Y',
                'DD-MMM-YY' => 'd-M-y',
                'dddd, DD MMMM YYYY' => 'l, d F Y',
            ];
            $php = $map[$vals['datef']] ?? 'd-m-Y';
            CompanyGlobalPreferences::put(['date_format' => $php], $company->id);
        }

        if (isset($vals['timef'])) {
            Settings::updateOrCreate(
                ['name' => 'time_format', 'company_id' => $company->id],
                ['value' => (string) $vals['timef'], 'company_id' => $company->id]
            );
        }
    }

    protected function loadTheme(Company $company): array
    {
        $branding = is_array($company->branding_json) ? $company->branding_json : [];
        if (is_string($company->branding_json)) {
            $branding = json_decode($company->branding_json, true) ?: [];
        }

        return $branding['company_details_theme'] ?? [];
    }

    protected function syncTheme(Company $company, $theme): void
    {
        if (! is_array($theme)) {
            return;
        }
        $branding = is_array($company->branding_json) ? $company->branding_json : [];
        if (is_string($company->branding_json)) {
            $branding = json_decode($company->branding_json, true) ?: [];
        }
        $branding['company_details_theme'] = $theme;
        $company->branding_json = $branding;
        if (! empty($theme['p'])) {
            $company->primary_color = $theme['p'];
        }
        $company->save();
    }

    protected function loadFieldOrders(Company $company): array
    {
        $raw = Settings::query()
            ->where('company_id', $company->id)
            ->where('name', 'company_details_field_orders')
            ->value('value');

        if (! $raw) {
            return [];
        }

        $decoded = json_decode((string) $raw, true);

        return is_array($decoded) ? $decoded : [];
    }

    protected function persistFieldOrders(Company $company, array $orders): void
    {
        Settings::updateOrCreate(
            ['name' => 'company_details_field_orders', 'company_id' => $company->id],
            ['value' => json_encode($orders), 'company_id' => $company->id]
        );
    }

    protected function logoUrl(?string $path): ?string
    {
        return $this->assetPublicUrl($path);
    }

    /**
     * Browser URL for stored assets. Uses the current request base path so previews
     * work when APP_URL host/port differs from the browser (XAMPP vs artisan serve).
     */
    protected function assetPublicUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }
        if (Str::startsWith($path, ['http://', 'https://', 'data:', 'blob:'])) {
            return $path;
        }

        $normalized = PublicStorageDisk::normalize($path);
        if ($normalized === '') {
            return null;
        }

        if (PublicStorageDisk::isCloud()) {
            return PublicStorageDisk::url($normalized) ?: null;
        }

        $base = rtrim((string) request()->getBasePath(), '/');

        return $base . '/storage/' . $normalized;
    }

    protected function expiryStatus($date): int
    {
        if (! $date) {
            return 1;
        }
        $ts = strtotime((string) $date);
        if ($ts === false) {
            return 1;
        }
        $days = (int) floor(($ts - strtotime('today')) / 86400);
        if ($days < 0) {
            return 0; // expired
        }

        return 1;
    }

    protected function expiryStatusLabel($date): string
    {
        if (! $date) {
            return 'none';
        }
        $ts = strtotime((string) $date);
        if ($ts === false) {
            return 'none';
        }
        $days = (int) floor(($ts - strtotime('today')) / 86400);
        if ($days < 0) {
            return 'exp';
        }
        if ($days <= 30) {
            return 'soon';
        }

        return 'ok';
    }
}
