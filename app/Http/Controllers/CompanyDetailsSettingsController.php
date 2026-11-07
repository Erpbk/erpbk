<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\CompanyLocationProfile;
use App\Models\Departments;
use App\Models\User;
use App\Services\Company\CompanyDetailsService;
use App\Services\Company\UserStampSignatureService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Validation\Rule;

class CompanyDetailsSettingsController extends Controller
{
    public function __construct(
        private CompanyDetailsService $service,
        private UserStampSignatureService $stampService
    ) {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        if (! auth()->user()->hasPermissionTo('settings_company_setting_view')) {
            abort(403, 'Unauthorized action.');
        }

        $company = $this->resolveCompany($request);
        if (! $company) {
            abort(404, 'Company not found.');
        }

        $bootstrap = $this->service->bootstrap($company);
        $layout = (View::shared('settings_panel') ?? false) ? 'layouts.settingsPanelLayout' : 'layouts.app';

        return view('settings.company_details.index', [
            'layout' => $layout,
            'bootstrap' => $bootstrap,
            'company' => $company,
        ]);
    }

    public function storeEntity(Request $request)
    {
        $this->authorizeEdit();
        $company = $this->resolveCompany($request);
        $data = $request->validate([
            // Only branches can be created here. New legal companies require a super admin.
            'type' => ['required', Rule::in(['br'])],
            'name' => ['required', 'string', 'max:255'],
            'emirate' => ['nullable', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:5'],
            'country' => ['nullable', 'string', 'max:255'],
        ]);

        $profile = $this->service->createEntity($company, $data);
        $bootstrap = $this->service->bootstrap($company);

        return response()->json([
            'success' => true,
            'entity_id' => $profile->id,
            'bootstrap' => $bootstrap,
            'message' => __('Created successfully.'),
        ]);
    }

    public function save(Request $request, $profile)
    {
        $this->authorizeEdit();
        $company = $this->resolveCompany($request);
        $model = $this->resolveProfile($company, $profile);

        $payload = $request->validate([
            'vals' => ['nullable', 'array'],
            'inh' => ['nullable', 'array'],
            'banks' => ['nullable', 'array'],
            'use_ho_bank' => ['nullable', 'boolean'],
            'docs_meta' => ['nullable', 'array'],
            'field_orders' => ['nullable', 'array'],
            'dirty_sections' => ['nullable', 'array'],
            'label' => ['nullable', 'string', 'max:255'],
            'place' => ['nullable', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:32'],
            'theme' => ['nullable', 'array'],
            'confirm_admin' => ['nullable', 'boolean'],
        ]);

        $dirty = (array) ($payload['dirty_sections'] ?? []);
        $needsConfirm = count(array_intersect($dirty, CompanyDetailsService::WARN_SECTIONS)) > 0;
        if ($needsConfirm && ! $request->boolean('confirm_admin')) {
            return response()->json([
                'success' => false,
                'needs_confirmation' => true,
                'sections' => array_values(array_intersect($dirty, CompanyDetailsService::WARN_SECTIONS)),
                'message' => __('Please confirm changes to sensitive settings.'),
            ], 422);
        }

        $isAdmin = (bool) auth()->user()?->isAdmin();
        $saved = $this->service->saveEntity($company, $model, $payload, $isAdmin);

        return response()->json([
            'success' => true,
            'entity' => $this->service->bootstrap($company),
            'message' => __('Settings saved.'),
            'profile_id' => $saved->id,
        ]);
    }

    public function destroy(Request $request, $profile)
    {
        $this->authorizeEdit();
        $company = $this->resolveCompany($request);
        $model = $this->resolveProfile($company, $profile);

        $this->service->deleteEntity($company, $model);

        return response()->json([
            'success' => true,
            'bootstrap' => $this->service->bootstrap($company),
            'message' => __('Deleted successfully.'),
        ]);
    }

    public function uploadAsset(Request $request, $profile)
    {
        $this->authorizeEdit();
        $company = $this->resolveCompany($request);
        $model = $this->resolveProfile($company, $profile);

        $data = $request->validate([
            'field' => ['required', Rule::in(['logo', 'stampimg', 'signimg', 'curimg'])],
            'file' => ['required', 'file', 'max:4096', 'mimes:jpg,jpeg,png,webp,gif,svg'],
        ]);

        $url = $this->service->uploadAsset($company, $model, $data['field'], $request->file('file'));
        $path = data_get($model->fresh()->vals, $data['field']);

        return response()->json([
            'success' => true,
            'url' => $url,
            'field' => $data['field'],
            'path' => $path,
        ]);
    }

    public function uploadDocument(Request $request, $profile)
    {
        $this->authorizeEdit();
        $company = $this->resolveCompany($request);
        $model = $this->resolveProfile($company, $profile);

        $data = $request->validate([
            'file' => ['required', 'file', 'max:5120', 'mimes:jpg,jpeg,png,webp,pdf,doc,docx'],
            'name' => ['nullable', 'string', 'max:255'],
            'key' => ['nullable', 'string', 'max:32'],
            'number' => ['nullable', 'string', 'max:100'],
            'expiry_date' => ['nullable', 'date'],
            'document_type_id' => ['nullable', 'integer'],
        ]);

        $file = $this->service->uploadDocument($company, $model, $request->file('file'), $data);

        return response()->json([
            'success' => true,
            'file' => [
                'id' => $file->id,
                'url' => $file->file_name ? asset('storage/' . ltrim($file->file_name, '/')) : null,
                'name' => data_get($file->field_values, 'original_name') ?: $file->name,
                'document_type_id' => $file->document_type_id,
            ],
            'bootstrap' => $this->service->bootstrap($company),
            'message' => __('Document saved.'),
        ]);
    }

    public function saveFieldOrders(Request $request)
    {
        $this->authorizeEdit();
        $company = $this->resolveCompany($request);
        $data = $request->validate([
            'field_orders' => ['required', 'array'],
        ]);

        $ho = CompanyLocationProfile::query()
            ->where('company_id', $company->id)
            ->where('entity_type', CompanyLocationProfile::TYPE_HEAD_OFFICE)
            ->first();

        if ($ho) {
            $this->service->saveEntity($company, $ho, [
                'field_orders' => $data['field_orders'],
                'dirty_sections' => [],
            ], (bool) auth()->user()?->isAdmin());
        }

        return response()->json(['success' => true]);
    }

    public function saveUserStamp(Request $request, int $user)
    {
        $this->authorizeStampManage($user);
        $company = $this->resolveCompany($request);
        $this->assertCompanyUser($company, $user);

        $payload = $request->validate([
            'signatory_name' => ['nullable', 'string', 'max:255'],
            'designation' => ['nullable', 'string', 'max:255'],
            'doc_matrix' => ['nullable', 'array'],
            'other_docs' => ['nullable', 'array'],
            'ap4' => ['nullable', 'boolean'],
            'ap5' => ['nullable', 'boolean'],
            'ap7' => ['nullable', 'boolean'],
            'ap8' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'stampimg' => ['nullable', 'string'],
            'signimg' => ['nullable', 'string'],
        ]);

        if (! isset($payload['other_docs'])) {
            $payload['other_docs'] = [
                'ap4' => (bool) ($payload['ap4'] ?? false),
                'ap5' => (bool) ($payload['ap5'] ?? false),
                'ap7' => (bool) ($payload['ap7'] ?? false),
                'ap8' => (bool) ($payload['ap8'] ?? true),
            ];
        }

        $row = $this->stampService->save($company, $user, $payload);

        return response()->json([
            'success' => true,
            'record' => $this->stampService->toClient($company, $user, $row),
            'message' => __('Stamp & signature saved.'),
        ]);
    }

    public function uploadUserStampAsset(Request $request, int $user)
    {
        $this->authorizeStampManage($user);
        $company = $this->resolveCompany($request);
        $this->assertCompanyUser($company, $user);

        $data = $request->validate([
            'field' => ['required', Rule::in(['stampimg', 'signimg'])],
            'file' => ['required', 'file', 'max:4096', 'mimes:jpg,jpeg,png,webp,gif,svg'],
        ]);

        $result = $this->stampService->uploadAsset($company, $user, $data['field'], $request->file('file'));

        return response()->json([
            'success' => true,
            'url' => $result['url'],
            'path' => $result['path'],
            'field' => $result['field'],
            'record' => $result['record'],
            'message' => __('Uploaded.'),
        ]);
    }

    public function storeDepartment(Request $request)
    {
        $this->authorizeEdit();
        $company = $this->resolveCompany($request);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'detail' => ['nullable', 'string', 'max:500'],
            'status' => ['nullable', 'boolean'],
        ]);

        $payload = [
            'name' => $data['name'],
            'detail' => $data['detail'] ?? null,
            'status' => array_key_exists('status', $data) ? (bool) $data['status'] : true,
        ];
        if (Schema::hasColumn('departments', 'company_id') && $company) {
            $payload['company_id'] = $company->id;
        }

        Departments::query()->create($payload);

        return response()->json([
            'success' => true,
            'departments' => $this->service->departmentsPayload($company),
            'message' => __('Department saved.'),
        ]);
    }

    public function updateDepartment(Request $request, int $department)
    {
        $this->authorizeEdit();
        $company = $this->resolveCompany($request);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'detail' => ['nullable', 'string', 'max:500'],
            'status' => ['nullable', 'boolean'],
        ]);

        $model = Departments::query()->where('id', $department)->firstOrFail();
        if (Schema::hasColumn('departments', 'company_id') && $company && (int) $model->company_id !== (int) $company->id) {
            abort(404);
        }

        $model->fill([
            'name' => $data['name'],
            'detail' => $data['detail'] ?? null,
            'status' => array_key_exists('status', $data) ? (bool) $data['status'] : (bool) $model->status,
        ])->save();

        return response()->json([
            'success' => true,
            'departments' => $this->service->departmentsPayload($company),
            'message' => __('Department updated.'),
        ]);
    }

    public function destroyDepartment(Request $request, int $department)
    {
        $this->authorizeEdit();
        $company = $this->resolveCompany($request);
        $model = Departments::query()->where('id', $department)->firstOrFail();
        if (Schema::hasColumn('departments', 'company_id') && $company && (int) $model->company_id !== (int) $company->id) {
            abort(404);
        }
        $model->delete();

        return response()->json([
            'success' => true,
            'departments' => $this->service->departmentsPayload($company),
            'message' => __('Department deleted.'),
        ]);
    }

    private function authorizeEdit(): void
    {
        if (
            ! auth()->user()->hasPermissionTo('settings_company_setting_create')
            || ! auth()->user()->hasPermissionTo('settings_company_setting_edit')
        ) {
            abort(403, 'Unauthorized action.');
        }
    }

    private function authorizeStampManage(int $targetUserId): void
    {
        $auth = auth()->user();
        if (! $auth) {
            abort(403, 'Unauthorized action.');
        }
        if ((int) $auth->id === $targetUserId) {
            // Users may manage their own stamp/signature images & name.
            if (
                $auth->can('settings_company_setting_edit')
                || $auth->can('settings_company_setting_view')
                || method_exists($auth, 'isAdmin') && $auth->isAdmin()
            ) {
                return;
            }
        }
        $this->authorizeEdit();
        if (! (method_exists($auth, 'isAdmin') && $auth->isAdmin())) {
            abort(403, 'Only administrators can manage another user\'s stamp & signature.');
        }
    }

    private function assertCompanyUser(?Company $company, int $userId): void
    {
        if (! $company) {
            abort(404, 'Company not found.');
        }
        $exists = User::query()
            ->where('company_id', $company->id)
            ->where('id', $userId)
            ->exists();
        if (! $exists) {
            abort(404, 'User not found.');
        }
    }

    private function resolveCompany(Request $request): ?Company
    {
        $fromRequest = $request->attributes->get('company');
        if ($fromRequest instanceof Company) {
            return $fromRequest;
        }

        $id = auth()->user()?->company_id;
        if (! $id) {
            return null;
        }

        return Company::query()->find((int) $id);
    }

    private function resolveProfile(?Company $company, mixed $profile): CompanyLocationProfile
    {
        if (! $company) {
            abort(404, 'Company not found.');
        }

        // Prefer explicit numeric id from the route; also accept the current request param.
        $raw = $profile;
        if ($raw === null || $raw === '' || ! is_numeric($raw)) {
            $raw = request()->route('profile');
        }

        $id = filter_var($raw, FILTER_VALIDATE_INT);
        if ($id === false || $id < 1) {
            abort(422, 'Invalid profile id.');
        }

        return CompanyLocationProfile::query()
            ->where('company_id', $company->id)
            ->where('id', $id)
            ->firstOrFail();
    }
}
