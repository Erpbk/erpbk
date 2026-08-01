<?php

use App\Helpers\General;
use App\Http\Controllers\AccountsController;
use App\Http\Controllers\Admin\AdminGlobalAccountsController;
use App\Http\Controllers\Admin\AdminBlogsController;
use App\Http\Controllers\Admin\AdminCompaniesController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminLoginController;
use App\Http\Controllers\Admin\AdminErpPermissionsController;
use App\Http\Controllers\Admin\AdminPermissionsController;
use App\Http\Controllers\Admin\AdminAuthBrandingController;
use App\Http\Controllers\Admin\AdminPolicyController;
use App\Http\Controllers\Admin\AdminRolesController;
use App\Http\Controllers\Admin\AdminTestimonialsController;
use App\Http\Controllers\Admin\AdminUsersController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\BanksController;
use App\Http\Controllers\BikeHistoryController;
use App\Http\Controllers\BikeMaintenanceController;
use App\Http\Controllers\BikeRegistrationController;
use App\Http\Controllers\BikeRegistrationStatusController;
use App\Http\Controllers\BikeRentCompaniesController;
use App\Http\Controllers\BikesController;
use App\Http\Controllers\ChequesController;
use App\Http\Controllers\Company\CompanyAuthController;
use App\Http\Controllers\Company\CompanyRegistrationController;
use App\Http\Controllers\CustomerInvoicesController;
use App\Http\Controllers\CustomersController;
use App\Http\Controllers\DepartmentsController;
use App\Http\Controllers\DropdownsController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\EmployeeInvoicesController;
use App\Http\Controllers\ErpSettingsController;
use App\Http\Controllers\AssetCategoryController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\FixedAssetController;
use App\Http\Controllers\FilesController;
use App\Http\Controllers\FuelCardController;
use App\Http\Controllers\FuelCardHistoryController;
use App\Http\Controllers\FuelCompaniesController;
use App\Http\Controllers\FuelDataController;
use App\Http\Controllers\GarageItemsController;
use App\Http\Controllers\GaragesController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\InventoryPurchaseController;
use App\Http\Controllers\ItemsController;
use App\Http\Controllers\LeasingCompaniesController;
use App\Http\Controllers\LeasingCompanyBillingInvoicesController;
use App\Http\Controllers\LoansController;
use App\Http\Controllers\LedgerController;
use App\Http\Controllers\AccountsReportController;
use App\Http\Controllers\pages\MiscError;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ReceiptController;
use App\Http\Controllers\RecruitersController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RiderActivitiesController;
use App\Http\Controllers\RiderAttendanceController;
use App\Http\Controllers\RiderEmailsController;
use App\Http\Controllers\riderhiringController;
use App\Http\Controllers\RiderInvoicesController;
use App\Http\Controllers\RidersController;
use App\Http\Controllers\RtaFinesController;
use App\Http\Controllers\SalikController;
use App\Http\Controllers\SimCompaniesController;
use App\Http\Controllers\SimHistoryController;
use App\Http\Controllers\SimInvoicesController;
use App\Http\Controllers\SimsController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\SupplierInvoicesController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserEmailSettingsController;
use App\Http\Controllers\UserTableSettingsController;
use App\Http\Controllers\VatController;
use App\Http\Controllers\VendorsController;
use App\Http\Controllers\InstallmentsController;
use App\Http\Controllers\VisaexpenseController;
use App\Http\Controllers\LicenseexpenseController;
use App\Http\Controllers\LicenseStatusController;
use App\Http\Controllers\LegalCaseController;
use App\Http\Controllers\LegalCaseStatusController;
use App\Http\Controllers\PassportHandoverController;
use App\Http\Controllers\RiderInventoryController;
use App\Http\Controllers\RiderInventoryItemController;
use App\Http\Controllers\RiderInventoryReportController;
use App\Http\Controllers\VisaStatusController;
use App\Http\Controllers\VouchersController;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;
use UniSharp\LaravelFilemanager\Lfm;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/
/* Route::any('/register', function () {
  return view('auth.register');
}); */

// ---------- Company registration (public) ----------
Route::get('company/register', [CompanyRegistrationController::class, 'showRegistrationForm'])->name('company.register');
Route::post('company/register/step1', [CompanyRegistrationController::class, 'submitStep1'])->name('company.register.step1.submit');
Route::get('company/register/otp', [CompanyRegistrationController::class, 'showOtpForm'])->name('company.register.otp');
Route::post('company/register/otp', [CompanyRegistrationController::class, 'verifyOtp'])->name('company.register.otp.verify');
Route::get('company/register/details', [CompanyRegistrationController::class, 'showDetailsForm'])->name('company.register.details');
Route::post('company/register/details', [CompanyRegistrationController::class, 'submitDetails'])->name('company.register.details.submit');
Route::get('company/register/pending', [CompanyRegistrationController::class, 'pending'])->name('company.register.pending');

// ---------- Company login (public): email + password, company resolved from user ----------
Route::redirect('/', '/company/login');
Route::redirect('/register', '/company/register');
Route::get('company/login', [CompanyAuthController::class, 'showLogin'])->name('company.login');
Route::post('company/login', [CompanyAuthController::class, 'login'])->name('company.login.submit');

Route::get('app/{company_slug}/login', [CompanyAuthController::class, 'showLoginForm'])->name('company.login-form');
Route::post('app/{company_slug}/login', [CompanyAuthController::class, 'login'])->name('company.login.legacy');

// ---------- Admin login (separate portal) ----------
Route::get('admin/login', [AdminLoginController::class, 'showLogin'])->name('admin.login')->middleware('guest:admin');
Route::post('admin/login', [AdminLoginController::class, 'login'])->name('admin.login.submit')->middleware('guest:admin');
Route::post('admin/logout', [AdminLoginController::class, 'logout'])->name('admin.logout')->middleware('auth:admin');

// Tenant UI lives in one Route::prefix('app/{company_slug}') group below. Avoid a second group with the same prefix (duplicate URIs break route names / matching).

// Settings panel must live under /app/{company_slug}/ so company context is active (same names: settings-panel.*)
Route::prefix('app/{company_slug}')->middleware(['web', 'tenant', 'company.routes', 'auth'])->group(function () {
    require base_path('routes/settings_panel.php');
});

// ---------- Admin: companies (global, shared DB) ----------
Route::prefix('admin')->middleware(['web', 'admin.guard', 'admin.auth'])->name('admin.')->group(function () {
    Route::get('dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');
    Route::get('companies', [AdminCompaniesController::class, 'index'])->middleware('admin.permission:companies_view')->name('companies.index');
    Route::get('companies/create', [AdminCompaniesController::class, 'create'])->middleware('admin.permission:companies_approve')->name('companies.create');
    Route::post('companies', [AdminCompaniesController::class, 'store'])->middleware('admin.permission:companies_approve')->name('companies.store');
    Route::get('companies/{company}', [AdminCompaniesController::class, 'show'])->middleware('admin.permission:companies_view')->name('companies.show');
    Route::post('companies/{company}/approve', [AdminCompaniesController::class, 'approve'])->middleware('admin.permission:companies_approve')->name('companies.approve');
    Route::post('companies/{company}/reject', [AdminCompaniesController::class, 'reject'])->middleware('admin.permission:companies_reject')->name('companies.reject');
    Route::get('companies/{company}/modules', [AdminCompaniesController::class, 'editModules'])->middleware('admin.permission:companies_approve')->name('companies.modules.edit');
    Route::put('companies/{company}/modules', [AdminCompaniesController::class, 'updateModules'])->middleware('admin.permission:companies_approve')->name('companies.modules.update');

    // Site Settings Modules (admin DB)
    Route::get('blogs', [AdminBlogsController::class, 'index'])->middleware('admin.permission:blogs_view')->name('blogs.index');
    Route::get('blogs/create', [AdminBlogsController::class, 'create'])->middleware('admin.permission:blogs_create')->name('blogs.create');
    Route::post('blogs', [AdminBlogsController::class, 'store'])->middleware('admin.permission:blogs_create')->name('blogs.store');
    Route::get('blogs/{blog}/edit', [AdminBlogsController::class, 'edit'])->middleware('admin.permission:blogs_edit')->name('blogs.edit');
    Route::put('blogs/{blog}', [AdminBlogsController::class, 'update'])->middleware('admin.permission:blogs_edit')->name('blogs.update');
    Route::delete('blogs/{blog}', [AdminBlogsController::class, 'destroy'])->middleware('admin.permission:blogs_delete')->name('blogs.destroy');

    Route::get('testimonials', [AdminTestimonialsController::class, 'index'])->middleware('admin.permission:testimonials_view')->name('testimonials.index');
    Route::get('testimonials/create', [AdminTestimonialsController::class, 'create'])->middleware('admin.permission:testimonials_create')->name('testimonials.create');
    Route::post('testimonials', [AdminTestimonialsController::class, 'store'])->middleware('admin.permission:testimonials_create')->name('testimonials.store');
    Route::get('testimonials/{testimonial}/edit', [AdminTestimonialsController::class, 'edit'])->middleware('admin.permission:testimonials_edit')->name('testimonials.edit');
    Route::put('testimonials/{testimonial}', [AdminTestimonialsController::class, 'update'])->middleware('admin.permission:testimonials_edit')->name('testimonials.update');
    Route::delete('testimonials/{testimonial}', [AdminTestimonialsController::class, 'destroy'])->middleware('admin.permission:testimonials_delete')->name('testimonials.destroy');

    Route::get('privacy-policy', [AdminPolicyController::class, 'editPrivacy'])->middleware('admin.permission:privacy_policy_view')->name('privacy-policy.edit');
    Route::post('privacy-policy', [AdminPolicyController::class, 'updatePrivacy'])->middleware('admin.permission:privacy_policy_edit')->name('privacy-policy.update');

    Route::get('terms-conditions', [AdminPolicyController::class, 'editTerms'])->middleware('admin.permission:terms_conditions_view')->name('terms-conditions.edit');
    Route::post('terms-conditions', [AdminPolicyController::class, 'updateTerms'])->middleware('admin.permission:terms_conditions_edit')->name('terms-conditions.update');

    Route::get('auth-branding', [AdminAuthBrandingController::class, 'edit'])->name('auth-branding.edit');
    Route::post('auth-branding', [AdminAuthBrandingController::class, 'update'])->name('auth-branding.update');

    // Admin roles (create/edit/delete from Users page)
    Route::get('roles/create', [AdminRolesController::class, 'create'])->middleware('admin.permission:users_edit')->name('roles.create');
    Route::post('roles', [AdminRolesController::class, 'store'])->middleware('admin.permission:users_edit')->name('roles.store');
    Route::get('roles/{role}/edit', [AdminRolesController::class, 'edit'])->middleware('admin.permission:users_edit')->name('roles.edit');
    Route::patch('roles/{role}', [AdminRolesController::class, 'update'])->middleware('admin.permission:users_edit')->name('roles.update');
    Route::delete('roles/{role}', [AdminRolesController::class, 'destroy'])->middleware('admin.permission:users_edit')->name('roles.destroy');

    // Users: role assignment + permission gating for admin sections
    Route::get('users', [AdminUsersController::class, 'index'])->middleware('admin.permission:users_view')->name('users.index');
    Route::get('users/create', [AdminUsersController::class, 'create'])->middleware('admin.permission:users_edit')->name('users.create');
    Route::post('users', [AdminUsersController::class, 'store'])->middleware('admin.permission:users_edit')->name('users.store');
    Route::get('users/{user}/edit', [AdminUsersController::class, 'edit'])->middleware('admin.permission:users_edit')->name('users.edit');
    Route::patch('users/{user}', [AdminUsersController::class, 'update'])->middleware('admin.permission:users_edit')->name('users.update');
    Route::delete('users/{user}', [AdminUsersController::class, 'destroy'])->middleware('admin.permission:users_edit')->name('users.destroy');
    Route::get('users/{user}/edit-roles', [AdminUsersController::class, 'editRoles'])->middleware('admin.permission:users_edit')->name('users.edit-roles');
    Route::post('users/{user}/roles', [AdminUsersController::class, 'updateRoles'])->middleware('admin.permission:users_edit')->name('users.update-roles');

    // Permissions module (Super Admin only)
    Route::get('permissions', [AdminPermissionsController::class, 'index'])->name('permissions.index');
    Route::get('permissions/create', [AdminPermissionsController::class, 'create'])->name('permissions.create');
    Route::post('permissions', [AdminPermissionsController::class, 'store'])->name('permissions.store');
    Route::get('permissions/{permission}/edit', [AdminPermissionsController::class, 'edit'])->name('permissions.edit');
    Route::patch('permissions/{permission}', [AdminPermissionsController::class, 'update'])->name('permissions.update');
    Route::post('permissions/roles/{role}', [AdminPermissionsController::class, 'updateRolePermissions'])->name('permissions.update-role');
    Route::delete('permissions/{permission}', [AdminPermissionsController::class, 'destroy'])->name('permissions.destroy');

    // ERP company permissions (Spatie permissions table on ERP database)
    Route::get('erp-permissions', [AdminErpPermissionsController::class, 'index'])->name('erp-permissions.index');
    Route::get('erp-permissions/create', [AdminErpPermissionsController::class, 'create'])->name('erp-permissions.create');
    Route::post('erp-permissions', [AdminErpPermissionsController::class, 'store'])->name('erp-permissions.store');
    Route::get('erp-permissions/{permission}/edit', [AdminErpPermissionsController::class, 'edit'])->name('erp-permissions.edit');
    Route::patch('erp-permissions/{permission}', [AdminErpPermissionsController::class, 'update'])->name('erp-permissions.update');
    Route::delete('erp-permissions/{permission}', [AdminErpPermissionsController::class, 'destroy'])->name('erp-permissions.destroy');

    // Global accounts (system-wide chart account registry)
    Route::get('global-accounts/accounts-by-type/{type}', [AdminGlobalAccountsController::class, 'accountsByType'])->name('global-accounts.accounts-by-type');
    Route::get('global-accounts/{globalAccount}/linked-account/edit', [AdminGlobalAccountsController::class, 'editLinkedAccount'])->name('global-accounts.linked-account.edit');
    Route::patch('global-accounts/{globalAccount}/linked-account', [AdminGlobalAccountsController::class, 'updateLinkedAccount'])->name('global-accounts.linked-account.update');
    Route::resource('global-accounts', AdminGlobalAccountsController::class)->except(['show']);

    // Legacy Account Fixing URLs → Global Accounts
    Route::redirect('accounts/fixed', '/admin/global-accounts')->name('accounts.fixed.redirect');
    Route::redirect('accounts/fixed/create', '/admin/global-accounts/create')->name('accounts.fixed.create.redirect');
});

// pages
Route::get('/pages/misc-error', [MiscError::class, 'index'])->name('pages-misc-error');

Route::prefix('app/{company_slug}')->middleware(['web', 'tenant', 'company.routes', 'auth'])->group(function () {

    Route::get('/', [HomeController::class, 'index'])->name('home');
    Route::get('/home', [HomeController::class, 'index'])->name('home-dashboard');
    Route::post('/logout', [CompanyAuthController::class, 'logout'])->name('company.logout');

    // Agreements (main app — centralized management)
    Route::prefix('agreements')->name('agreements.')->group(function () {
        Route::get('/', [App\Http\Controllers\AgreementSettingsController::class, 'index'])->name('index');
        Route::get('/create', [App\Http\Controllers\AgreementSettingsController::class, 'createAgreement'])->name('create-agreement');
        Route::post('/store', [App\Http\Controllers\AgreementSettingsController::class, 'storeAgreement'])->name('store-agreement');
        Route::get('/categories/{category}', [App\Http\Controllers\AgreementSettingsController::class, 'showAgreement'])->name('show-agreement')->whereNumber('category');
        Route::get('/categories/{category}/edit', [App\Http\Controllers\AgreementSettingsController::class, 'editAgreement'])->name('edit-agreement')->whereNumber('category');
        Route::put('/categories/{category}', [App\Http\Controllers\AgreementSettingsController::class, 'updateAgreement'])->name('update-agreement')->whereNumber('category');
        Route::delete('/categories/{category}', [App\Http\Controllers\AgreementSettingsController::class, 'destroyAgreement'])->name('destroy-agreement')->whereNumber('category');
        Route::post('/categories/{category}/toggle-status', [App\Http\Controllers\AgreementSettingsController::class, 'toggleAgreementStatus'])->name('toggle-agreement-status')->whereNumber('category');
        Route::get('/categories/{category}/templates', [App\Http\Controllers\AgreementSettingsController::class, 'templates'])->name('templates')->whereNumber('category');
        Route::get('/categories/{category}/templates/create', [App\Http\Controllers\AgreementSettingsController::class, 'create'])->name('create')->whereNumber('category');
        Route::post('/categories/{category}/templates', [App\Http\Controllers\AgreementSettingsController::class, 'store'])->name('store')->whereNumber('category');
        Route::get('/templates/{id}/edit', [App\Http\Controllers\AgreementSettingsController::class, 'edit'])->name('edit')->whereNumber('id');
        Route::put('/templates/{id}', [App\Http\Controllers\AgreementSettingsController::class, 'update'])->name('update')->whereNumber('id');
        Route::delete('/templates/{id}', [App\Http\Controllers\AgreementSettingsController::class, 'destroy'])->name('destroy')->whereNumber('id');
        Route::post('/templates/{id}/duplicate', [App\Http\Controllers\AgreementSettingsController::class, 'duplicate'])->name('duplicate')->whereNumber('id');
        Route::post('/templates/{id}/set-default', [App\Http\Controllers\AgreementSettingsController::class, 'setDefault'])->name('set-default')->whereNumber('id');
        Route::post('/templates/{id}/toggle-status', [App\Http\Controllers\AgreementSettingsController::class, 'toggleStatus'])->name('toggle-status')->whereNumber('id');
        Route::get('/templates/{id}/preview', [App\Http\Controllers\AgreementSettingsController::class, 'preview'])->name('preview')->whereNumber('id');
        Route::get('/templates/{id}/preview-pdf', [App\Http\Controllers\AgreementSettingsController::class, 'previewPdf'])->name('preview-pdf')->whereNumber('id');
    });

    // Module Agreements — register before employees/riders resource routes ({module}/agreements must not match {employee} or {rider})
    $agreementModulePattern = app(\App\Services\Agreements\AgreementModuleService::class)->routePattern();
    Route::prefix('{module}/agreements')->where(['module' => $agreementModulePattern])->name('module-agreements.')->group(function () {
        Route::get('/', [App\Http\Controllers\ModuleAgreementController::class, 'index'])->name('index');
        Route::get('/categories/{category}', [App\Http\Controllers\ModuleAgreementController::class, 'show'])->name('show')->whereNumber('category');
        Route::get('/templates/{template}/edit', [App\Http\Controllers\ModuleAgreementController::class, 'editTemplate'])->name('templates.edit')->whereNumber('template');
        Route::put('/templates/{template}', [App\Http\Controllers\ModuleAgreementController::class, 'updateTemplate'])->name('templates.update')->whereNumber('template');
        Route::post('/categories/{category}/templates/{template}/assign', [App\Http\Controllers\ModuleAgreementController::class, 'assignContractTemplate'])->name('templates.assign')->whereNumber(['category', 'template']);
        Route::get('/templates/{template}/preview', [App\Http\Controllers\ModuleAgreementController::class, 'previewTemplate'])->name('templates.preview')->whereNumber('template');
        Route::get('/templates/{template}/preview-pdf', [App\Http\Controllers\ModuleAgreementController::class, 'previewTemplatePdf'])->name('templates.preview-pdf')->whereNumber('template');
    });

    // Module record contracts (listing action → contract modal, PDF, email)
    Route::prefix('{module}/records/{record}/contracts')->where(['module' => $agreementModulePattern])->whereNumber('record')->name('module-contracts.')->group(function () {
        Route::get('/', [App\Http\Controllers\ModuleContractController::class, 'modal'])->name('modal');
        Route::get('/preview', [App\Http\Controllers\ModuleContractController::class, 'preview'])->name('preview');
        Route::get('/pdf', [App\Http\Controllers\ModuleContractController::class, 'pdf'])->name('pdf');
        Route::post('/email', [App\Http\Controllers\ModuleContractController::class, 'email'])->name('email');
    });

    Route::resource('items', ItemsController::class);
    Route::resource('garage-items', GarageItemsController::class);
    Route::get('garage-items/{id}/vouchers', [GarageItemsController::class, 'vouchers'])->name('garage-items.vouchers');

    Route::any('/user/profile', [UserController::class, 'profile'])->name('profile');
    Route::get('/user/email-settings', [UserEmailSettingsController::class, 'edit'])->name('user.email-settings.edit');
    Route::post('/user/email-settings', [UserEmailSettingsController::class, 'update'])->name('user.email-settings.update');
    Route::any('/user/services/{id}', [UserController::class, 'services'])->name('user_services');

    Route::get('bikes/import', [BikesController::class, 'importbikes'])->name('bikes.import');
    Route::post('bikes/import', [BikesController::class, 'processImport'])->name('bikes.processImport');
    Route::get('bikes/export', [BikesController::class, 'exportCustomizableBikes'])->name('bikes.export');
    Route::get('bikes/download-template', [BikesController::class, 'downloadSampleTemplate'])->name('bikes.download-template');

    Route::any('bikes/assign_rider/{id?}', [BikesController::class, 'assign_rider'])->name('bikes.assign_rider');
    Route::any('bikes/assignrider/{id?}', [BikesController::class, 'assignrider'])->name('bikes.assignrider');
    Route::any('bikes/leasing-return/{id}', [BikesController::class, 'leasingReturn'])->name('bikes.leasingReturn');
    Route::any('bikes/change-project/{id}', [BikesController::class, 'changeProject'])->name('bikes.change_project');
    Route::get('bikes/contracts/{id?}', [BikesController::class, 'assignContract'])->name('bikes.assignContract');
    Route::get('bikes/contract/{id?}', [BikesController::class, 'returnContract'])->name('bikes.returnContract');
    Route::any('bikes/contract_upload/{id?}', [BikesController::class, 'contract_upload'])->name('bike_contract_upload');
    Route::get('bikes/delete/{id}', [BikesController::class, 'destroy'])->name('bikes.delete');
    Route::get('bike/files/{id}', [BikesController::class, 'files'])->name('bikes.files');
    Route::get('bike/maintenance/{id}', [BikesController::class, 'maintenance'])->name('bikes.maintenance');

    Route::resource('bikes', BikesController::class);

    Route::resource('bikeMaintenance', BikeMaintenanceController::class);
    Route::any('bike-maintenance/{bike}/edit', [BikeMaintenanceController::class, 'edit'])->name('bike-maintenance.editForm');
    Route::any('bike-maintenance/{bike}/update', [BikeMaintenanceController::class, 'update'])->name('bike-maintenance.update');
    Route::get('bike-maintenance/{maintenance}/invoice', [BikeMaintenanceController::class, 'Invoice'])->name('bike-maintenance.invoice');
    Route::get('bike-maintenance/{maintenance}/sticker', [BikeMaintenanceController::class, 'sticker'])->name('bike-maintenance.sticker');

    Route::get('bikes/import-bikes', [BikesController::class, 'importbikes'])->name('bikes.importbikes');
    Route::post('bikes/process-import', [BikesController::class, 'processImport'])->name('bikes.process-import');

    Route::resource('customers', CustomersController::class)->parameters(['customers' => 'id']);
    Route::get('customer/ledger/{id}', [CustomersController::class, 'ledger'])->name('customer.ledger');
    Route::get('customer/files/{id}', [CustomersController::class, 'files'])->name('customer.files');
    Route::get('customer/invoices/{id}', [CustomersController::class, 'invoices'])->name('customer.invoices');
    Route::get('customer/receipts', [CustomersController::class, 'cReceipts'])->name('customer.receipts');
    Route::get('customers/payments/{id}', [CustomersController::class, 'payments'])->name('customers.payments');
    Route::get('customers/receipts/{id}', [CustomersController::class, 'receipts'])->name('customers.receipts');
    Route::get('customer/inventory/{id}', [CustomersController::class, 'inventory'])->name('customer.inventory');
    // Customers Trash Routes
    Route::get('customers/trash', [CustomersController::class, 'trash'])->name('customers.trash');
    Route::post('customers/trash/{id}/restore', [CustomersController::class, 'restoreTrash'])->name('customers.restore');
    Route::delete('customers/trash/{id}/force-destroy', [CustomersController::class, 'forceDestroyTrash'])->name('customers.force-destroy');

    Route::get('rtaFines/import', [RtaFinesController::class, 'importForm'])->name('rtaFines.import.form');
    Route::post('rtaFines/import', [RtaFinesController::class, 'import'])->name('rtaFines.import');

    Route::resource('rtaFines', RtaFinesController::class)->except(['show']);
    Route::get('rtaFines/invoice/{id}', [RtaFinesController::class, 'show'])->name('rtaFines.show');
    Route::post('rtaFines/store', [RtaFinesController::class, 'store']);
    Route::get('rtaFines/edit/{id}', [RtaFinesController::class, 'edit']);
    Route::post('rtaFines/update/{id}', [RtaFinesController::class, 'update'])->name('rtaFines.update');
    Route::get('rtaFines/create', [RtaFinesController::class, 'create'])->name('rtaFines.create');
    Route::any('rtaFines/attach_file/{id}', [RtaFinesController::class, 'fileUpload'])->name('rtaFines.fileupload');

    Route::post('rtaFines/accountcreate', [RtaFinesController::class, 'accountcreate'])->name('rtaFines.accountcreate');
    Route::get('rtaFines/tickets', [RtaFinesController::class, 'tickets'])->name('rtaFines.tickets');
    Route::get('rtaFines/paid', [RtaFinesController::class, 'paid'])->name('rtaFines.paid');
    Route::post('rtaFines/payfine', [RtaFinesController::class, 'payfine'])->name('rtaFines.payfine');
    Route::get('rtaFines/viewvoucher/{id}', [RtaFinesController::class, 'payForm'])->name('rtaFines.viewvoucher');
    Route::get('rtaFines/getrider/{id}', [RtaFinesController::class, 'getrider'])->name('rtaFines.getrider');

    Route::get('/customer_invoices/{id}/edit', [CustomerInvoicesController::class, 'edit'])->name('customer_invoice.edit');
    Route::get('/customer_invoices/{id}/clone', [CustomerInvoicesController::class, 'clone'])->name('customer_invoice.clone');
    Route::resource('customer_invoices', CustomerInvoicesController::class);

    Route::get('employees/payments', [EmployeeController::class, 'payment'])->name('employee.payment');
    Route::get('employees/advanceloan/{id}', [EmployeeController::class, 'advanceloan'])->name('employees.advanceloan');
    Route::get('employees/penalty/{id}', [EmployeeController::class, 'penalty'])->name('employees.penalty');
    Route::get('employees/incentive/{id}', [EmployeeController::class, 'incentive'])->name('employees.incentive');
    Route::post('employees/storeadvanceloan', [EmployeeController::class, 'storeadvanceloan'])->name('employees.storeadvanceloan');
    Route::post('employees/storepenalty', [EmployeeController::class, 'storepenalty'])->name('employees.storepenalty');
    Route::post('employees/storeincentive', [EmployeeController::class, 'storeincentive'])->name('employees.storeincentive');
    Route::resource('employees', EmployeeController::class);
    Route::get('/employees/{id}/ledger', [EmployeeController::class, 'ledger'])->name('employee.ledger');
    Route::get('/employees/{id}/files', [EmployeeController::class, 'files'])->name('employee.files');
    Route::get('/employees/{id}/salary', [EmployeeController::class, 'salary'])->name('employee.salary');
    Route::get('/employees/{id}/attendance', [EmployeeController::class, 'attendance'])->name('employee.attendance');
    Route::get('/employees/{id}/leaves', [EmployeeController::class, 'leaves'])->name('employee.leaves');
    Route::get('/employees/{id}/history', [EmployeeController::class, 'history'])->name('employee.history');
    Route::any('/employees/sendemail/{id}', [EmployeeController::class, 'sendEmail'])->name('employee.sendemail');
    Route::get('/employees/{id}/voucher', [EmployeeController::class, 'voucher'])->name('employees.voucher');
    Route::post('/employees/update-status', [EmployeeController::class, 'updateStatus'])->name('employee.update-status');
    Route::post('/employees/update-profile-field', [EmployeeController::class, 'updateProfileField'])->name('employee.update-profile-field');
    Route::post('/employees/{id}/update-section', [EmployeeController::class, 'updateSection'])->name('employees.updateSection');

    Route::get('attendance/summary', [AttendanceController::class, 'summary'])->name('attendance.summary');
    Route::get('attendance/export', [AttendanceController::class, 'export'])->name('attendance.export');
    Route::get('attendance/summary/export', [AttendanceController::class, 'exportSummary'])->name('attendance.summary.export');
    Route::get('/attendance/create/{refType}', [AttendanceController::class, 'create'])->name('attendance.create');
    Route::resource('attendance', AttendanceController::class)->except(['create']);
    Route::post('attendance/bulk-mark', [AttendanceController::class, 'bulkMark'])->name('attendance.bulk-mark');
    Route::get('attendance/users/{refType}', [AttendanceController::class, 'getUsers'])->name('attendance.users');

    // Visa expense custom routes (register before resource to avoid {VisaExpense} shadowing)
    Route::get('VisaExpense/generatentries/{id}', [VisaexpenseController::class, 'generatentries'])->name('VisaExpense.generatentries');
    Route::get('VisaExpense/create/{id}', [VisaexpenseController::class, 'create'])->name('VisaExpense.create');
    Route::get('VisaExpense/edit/{id}', [VisaexpenseController::class, 'edit'])->name('VisaExpense.edit');
    Route::get('VisaExpense/delete/{id}', [VisaexpenseController::class, 'destroy'])->name('VisaExpense.delete');
    Route::get('VisaExpense/viewvoucher/{id}', [VisaexpenseController::class, 'viewvoucher'])->name('VisaExpense.viewvoucher');
    Route::get('VisaExpense/getrider/{id}', [VisaexpenseController::class, 'getrider']);

    Route::resource('VisaExpense', VisaexpenseController::class)->except([
        'create',
        'edit',
        'store',
        'update',
        'destroy',
        'show',
    ]);

    // Visa Status Management Routes
    Route::resource('visa-statuses', VisaStatusController::class);
    Route::post('visa-statuses/reorder', [VisaStatusController::class, 'reorder'])->name('visa-statuses.reorder');
    Route::get('visa-statuses/{id}/toggle-active', [VisaStatusController::class, 'toggleActive'])->name('visa-statuses.toggle-active');
    Route::resource('license-statuses', LicenseStatusController::class);
    Route::post('license-statuses/reorder', [LicenseStatusController::class, 'reorder'])->name('license-statuses.reorder');
    Route::get('license-statuses/{id}/toggle-active', [LicenseStatusController::class, 'toggleActive'])->name('license-statuses.toggle-active');
    Route::post('VisaExpense/store', [VisaexpenseController::class, 'store'])->name('VisaExpense.store');
    Route::post('VisaExpense/inline-update', [VisaexpenseController::class, 'inlineUpdate'])->name('VisaExpense.inlineUpdate');
    Route::post('VisaExpense/update', [VisaexpenseController::class, 'update'])->name('VisaExpense.update');
    Route::any('VisaExpense/attach_file/{id}', [VisaexpenseController::class, 'fileUpload'])->name('VisaExpense.fileupload');

    // Settings panel: registered under /app/{company_slug}/ — see routes/settings_panel.php

    Route::post('VisaExpense/getVisaStatusFee', [VisaexpenseController::class, 'getVisaStatusFee'])->name('VisaExpense.getVisaStatusFee');

    // Installments module routes
    Route::get('Installments/createInstallmentPlanForm/{riderId}', [InstallmentsController::class, 'createInstallmentPlanForm'])->name('Installments.createInstallmentPlanForm');
    Route::get('Installments/installmentPlan/{id}', [InstallmentsController::class, 'installmentPlan'])->name('Installments.installmentPlan');
    Route::post('Installments/createInstallmentPlan', [InstallmentsController::class, 'createInstallmentPlan'])->name('Installments.createInstallmentPlan');
    Route::post('Installments/payInstallment', [InstallmentsController::class, 'payInstallment'])->name('Installments.payInstallment');
    Route::post('Installments/updateInstallmentField', [InstallmentsController::class, 'updateInstallmentField'])->name('Installments.updateInstallmentField');
    Route::post('Installments/finalizePayment', [InstallmentsController::class, 'finalizePayment'])->name('Installments.finalizePayment');
    Route::get('Installments/deleteInstallment/{id}', [InstallmentsController::class, 'deleteInstallment'])->name('Installments.deleteInstallment')->whereNumber('id');
    Route::get('Installments/generateInstallmentInvoice/{riderId}', [InstallmentsController::class, 'generateInstallmentInvoice'])->name('Installments.generateInstallmentInvoice');
    Route::get('Installments/autoMarkInstallments/{riderId?}', [InstallmentsController::class, 'autoMarkInstallmentsAsPaid'])->name('Installments.autoMarkInstallments');
    Route::post('Installments/recalculateInstallments', [InstallmentsController::class, 'recalculateInstallments'])->name('Installments.recalculateInstallments');
    Route::get('Installments', [InstallmentsController::class, 'index'])->name('Installments.index');

    Route::post('accountcreate', [VisaexpenseController::class, 'accountcreate'])->name('VisaExpense.accountcreate');
    Route::get('VisaExpense/eligible-categories/{riderId}', [VisaexpenseController::class, 'eligibleRenewalCategories'])->name('VisaExpense.eligibleRenewalCategories');
    Route::post('editaccount', [VisaexpenseController::class, 'editaccount'])->name('VisaExpense.editaccount');
    Route::get('VisaExpense/deleteaccount/{id}', [VisaexpenseController::class, 'deleteaccount'])->name('VisaExpense.deleteaccount');
    Route::post('VisaExpense/payfine', [VisaexpenseController::class, 'payfine'])->name('VisaExpense.payfine');
    Route::get('VisaExpense/edit-voucher-credit/{visaExpense}', [VisaexpenseController::class, 'editVoucherCreditForm'])->name('VisaExpense.editVoucherCreditForm');
    Route::post('VisaExpense/update-voucher-credit', [VisaexpenseController::class, 'updateVoucherCredit'])->name('VisaExpense.updateVoucherCredit');

    // License Expense custom routes (register before resource to avoid {LicenseExpense} shadowing)
    Route::get('LicenseExpense/generatentries/{id}', [LicenseexpenseController::class, 'generatentries'])->name('LicenseExpense.generatentries');
    Route::get('LicenseExpense/create/{id}', [LicenseexpenseController::class, 'create'])->name('LicenseExpense.create');
    Route::get('LicenseExpense/edit/{id}', [LicenseexpenseController::class, 'edit'])->name('LicenseExpense.edit');
    Route::get('LicenseExpense/delete/{id}', [LicenseexpenseController::class, 'destroy'])->name('LicenseExpense.delete');
    Route::get('LicenseExpense/viewvoucher/{id}', [LicenseexpenseController::class, 'viewvoucher'])->name('LicenseExpense.viewvoucher');

    Route::resource('LicenseExpense', LicenseexpenseController::class)->except([
        'create',
        'edit',
        'store',
        'update',
        'destroy',
        'show',
    ]);

    Route::post('LicenseExpense/store', [LicenseexpenseController::class, 'store'])->name('LicenseExpense.store');
    Route::post('LicenseExpense/inline-update', [LicenseexpenseController::class, 'inlineUpdate'])->name('LicenseExpense.inlineUpdate');
    Route::post('LicenseExpense/update', [LicenseexpenseController::class, 'update'])->name('LicenseExpense.update');
    Route::any('LicenseExpense/attach_file/{id}', [LicenseexpenseController::class, 'fileUpload'])->name('LicenseExpense.fileupload');
    Route::post('LicenseExpense/getLicenseStatusFee', [LicenseexpenseController::class, 'getLicenseStatusFee'])->name('LicenseExpense.getLicenseStatusFee');
    Route::post('license-accountcreate', [LicenseexpenseController::class, 'accountcreate'])->name('LicenseExpense.accountcreate');
    Route::post('license-editaccount', [LicenseexpenseController::class, 'editaccount'])->name('LicenseExpense.editaccount');
    Route::get('LicenseExpense/deleteaccount/{id}', [LicenseexpenseController::class, 'deleteaccount'])->name('LicenseExpense.deleteaccount');
    Route::post('LicenseExpense/payfine', [LicenseexpenseController::class, 'payfine'])->name('LicenseExpense.payfine');
    Route::get('LicenseExpense/edit-voucher-credit/{licenseExpense}', [LicenseexpenseController::class, 'editVoucherCreditForm'])->name('LicenseExpense.editVoucherCreditForm');
    Route::post('LicenseExpense/update-voucher-credit', [LicenseexpenseController::class, 'updateVoucherCredit'])->name('LicenseExpense.updateVoucherCredit');

    // Legal Case custom routes (register before resource to avoid {LegalCase} shadowing)
    Route::get('LegalCase/generatentries/{id}', [LegalCaseController::class, 'generatentries'])->name('LegalCase.generatentries');
    Route::get('LegalCase/create/{id}', [LegalCaseController::class, 'create'])->name('LegalCase.create');
    Route::get('LegalCase/edit/{id}', [LegalCaseController::class, 'edit'])->name('LegalCase.edit');
    Route::get('LegalCase/delete/{id}', [LegalCaseController::class, 'destroy'])->name('LegalCase.delete');
    Route::resource('LegalCase', LegalCaseController::class)->only(['index']);
    Route::resource('legal-case-statuses', LegalCaseStatusController::class);
    Route::post('legal-case-statuses/reorder', [LegalCaseStatusController::class, 'reorder'])->name('legal-case-statuses.reorder');
    Route::get('legal-case-statuses/{id}/toggle-active', [LegalCaseStatusController::class, 'toggleActive'])->name('legal-case-statuses.toggle-active');
    Route::post('LegalCase/store', [LegalCaseController::class, 'store'])->name('LegalCase.store');
    Route::post('LegalCase/inline-update', [LegalCaseController::class, 'inlineUpdate'])->name('LegalCase.inlineUpdate');
    Route::post('LegalCase/update', [LegalCaseController::class, 'update'])->name('LegalCase.update');
    Route::post('LegalCase/complete-step', [LegalCaseController::class, 'completeStep'])->name('LegalCase.completeStep');
    Route::post('legalcase-accountcreate', [LegalCaseController::class, 'accountcreate'])->name('LegalCase.accountcreate');
    Route::post('legalcase-editaccount', [LegalCaseController::class, 'editaccount'])->name('LegalCase.editaccount');
    Route::get('LegalCase/deleteaccount/{id}', [LegalCaseController::class, 'deleteaccount'])->name('LegalCase.deleteaccount');

    // Passport Handover
    Route::get('passport-handover', [PassportHandoverController::class, 'index'])->name('passportHandover.index');
    Route::get('passport-handover/{type}/{id}/history', [PassportHandoverController::class, 'history'])
        ->where(['type' => 'rider|employee'])
        ->name('passportHandover.history');
    Route::get('passport-handover/{type}/{id}/issue', [PassportHandoverController::class, 'issueForm'])
        ->where(['type' => 'rider|employee'])
        ->name('passportHandover.issueForm');
    Route::post('passport-handover/{type}/{id}/issue', [PassportHandoverController::class, 'issueStore'])
        ->where(['type' => 'rider|employee'])
        ->name('passportHandover.issueStore');
    Route::get('passport-handover/{type}/{id}/return', [PassportHandoverController::class, 'returnForm'])
        ->where(['type' => 'rider|employee'])
        ->name('passportHandover.returnForm');
    Route::post('passport-handover/{type}/{id}/return', [PassportHandoverController::class, 'returnStore'])
        ->where(['type' => 'rider|employee'])
        ->name('passportHandover.returnStore');
    Route::get('passport-handover/contracts/issue/{id}', [PassportHandoverController::class, 'issueContract'])->name('passportHandover.issueContract');
    Route::get('passport-handover/contracts/return/{id}', [PassportHandoverController::class, 'returnContract'])->name('passportHandover.returnContract');

    // Rider Inventory
    Route::get('RiderInventory/reports/data', [RiderInventoryReportController::class, 'data'])->name('RiderInventory.reports.data');
    Route::get('RiderInventory/reports', [RiderInventoryReportController::class, 'index'])->name('RiderInventory.reports');
    Route::get('RiderInventory/show/{riderId}', [RiderInventoryController::class, 'show'])->name('RiderInventory.show');
    Route::get('RiderInventory/assign/{riderId?}', [RiderInventoryController::class, 'assignForm'])->name('RiderInventory.assignForm')->where(['riderId' => '[0-9]+']);
    Route::post('RiderInventory/assign', [RiderInventoryController::class, 'assignStore'])->name('RiderInventory.assignStore');
    Route::get('RiderInventory/return/{assignmentId}', [RiderInventoryController::class, 'returnForm'])->name('RiderInventory.returnForm');
    Route::post('RiderInventory/return/{assignmentId}', [RiderInventoryController::class, 'returnStore'])->name('RiderInventory.returnStore');
    Route::get('RiderInventory/lost/{assignmentId}', [RiderInventoryController::class, 'lostForm'])->name('RiderInventory.lostForm');
    Route::post('RiderInventory/lost/{assignmentId}', [RiderInventoryController::class, 'markLost'])->name('RiderInventory.markLost');
    Route::get('RiderInventory/change-status/{assignmentId}', [RiderInventoryController::class, 'changeStatusForm'])->name('RiderInventory.changeStatusForm');
    Route::post('RiderInventory/change-status/{assignmentId}', [RiderInventoryController::class, 'changeStatusStore'])->name('RiderInventory.changeStatusStore');
    Route::delete('RiderInventory/assignment/{assignmentId}', [RiderInventoryController::class, 'destroyAssignment'])->name('RiderInventory.destroyAssignment');
    Route::get('RiderInventory/assignment-contract/{riderId}', [RiderInventoryController::class, 'assignmentContract'])->name('RiderInventory.assignmentContract');
    Route::get('RiderInventory/return-contract/{riderId}', [RiderInventoryController::class, 'returnContractForm'])->name('RiderInventory.returnContractForm');
    Route::post('RiderInventory/return-contract/{riderId}', [RiderInventoryController::class, 'returnContractProcess'])->name('RiderInventory.returnContractProcess');
    Route::get('RiderInventory/return-contract-document/{contractId}', [RiderInventoryController::class, 'returnContractDocument'])->name('RiderInventory.returnContractDocument');
    Route::get('RiderInventory/return-to-customer', [RiderInventoryController::class, 'returnToCustomerForm'])->name('RiderInventory.returnToCustomerForm');
    Route::get('RiderInventory/return-to-customer/assignments', [RiderInventoryController::class, 'returnToCustomerAssignments'])->name('RiderInventory.returnToCustomerAssignments');
    Route::post('RiderInventory/return-to-customer', [RiderInventoryController::class, 'returnToCustomerStore'])->name('RiderInventory.returnToCustomerStore');
    Route::get('RiderInventory', [RiderInventoryController::class, 'index'])->name('RiderInventory.index');

    Route::get('fixed-assets/category-defaults/{categoryId}', [FixedAssetController::class, 'categoryDefaults'])->name('fixed-assets.category-defaults')->whereNumber('categoryId');
    Route::get('fixed-assets/delete/{id}', [FixedAssetController::class, 'destroy'])->name('fixed-assets.delete')->whereNumber('id');
    Route::resource('fixed-assets', FixedAssetController::class);
    Route::get('asset-categories/delete/{id}', [AssetCategoryController::class, 'destroy'])->name('asset-categories.delete')->whereNumber('id');
    Route::resource('asset-categories', AssetCategoryController::class)->except(['show']);

    Route::resource('rider-inventory-items', RiderInventoryItemController::class);
    Route::post('rider-inventory-items/reorder', [RiderInventoryItemController::class, 'reorder'])->name('rider-inventory-items.reorder');
    Route::get('rider-inventory-items/{id}/toggle-active', [RiderInventoryItemController::class, 'toggleActive'])->name('rider-inventory-items.toggle-active');

    Route::post('bike-registration-statuses/reorder', [BikeRegistrationStatusController::class, 'reorder'])->name('bike-registration-statuses.reorder');
    Route::get('bike-registration-statuses/{id}/toggle-active', [BikeRegistrationStatusController::class, 'toggleActive'])->name('bike-registration-statuses.toggle-active');
    Route::resource('bike-registration-statuses', BikeRegistrationStatusController::class);
    Route::post('BikeRegistration/store', [BikeRegistrationController::class, 'store'])->name('BikeRegistration.store');
    Route::post('BikeRegistration/inline-update', [BikeRegistrationController::class, 'inlineUpdate'])->name('BikeRegistration.inlineUpdate');
    Route::get('BikeRegistration/create/{id}', [BikeRegistrationController::class, 'create'])->name('BikeRegistration.create');
    Route::get('BikeRegistration/edit/{id}', [BikeRegistrationController::class, 'edit'])->name('BikeRegistration.edit');
    Route::post('BikeRegistration/update', [BikeRegistrationController::class, 'update'])->name('BikeRegistration.update');
    Route::get('BikeRegistration/delete/{id}', [BikeRegistrationController::class, 'destroy'])->name('BikeRegistration.delete');
    Route::post('BikeRegistration/br-accountcreate', [BikeRegistrationController::class, 'accountcreate'])->name('BikeRegistration.accountcreate');
    Route::post('BikeRegistration/br-editaccount', [BikeRegistrationController::class, 'editaccount'])->name('BikeRegistration.editaccount');
    Route::get('BikeRegistration/deleteaccount/{id}', [BikeRegistrationController::class, 'deleteaccount'])->name('BikeRegistration.deleteaccount');
    Route::get('BikeRegistration/generatentries/{id}', [BikeRegistrationController::class, 'generatentries'])->name('BikeRegistration.generatentries');
    Route::post('BikeRegistration/payfine', [BikeRegistrationController::class, 'payfine'])->name('BikeRegistration.payfine');
    Route::get('BikeRegistration/edit-voucher-credit/{bikeRegistration}', [BikeRegistrationController::class, 'editVoucherCreditForm'])->name('BikeRegistration.editVoucherCreditForm');
    Route::post('BikeRegistration/update-voucher-credit', [BikeRegistrationController::class, 'updateVoucherCredit'])->name('BikeRegistration.updateVoucherCredit');
    Route::get('BikeRegistration/viewvoucher/{id}', [BikeRegistrationController::class, 'viewvoucher'])->name('BikeRegistration.viewvoucher');
    Route::post('BikeRegistration/get-registration-status-fee', [BikeRegistrationController::class, 'getRegistrationStatusFee'])->name('BikeRegistration.getRegistrationStatusFee');
    Route::get('BikeRegistration', [BikeRegistrationController::class, 'index'])->name('BikeRegistration.index');
    Route::get('BikeRegistration/{id}', [BikeRegistrationController::class, 'show'])->name('BikeRegistration.show')->whereNumber('id');

    Route::match(['get', 'post'], 'sims/assign/{id}', [SimsController::class, 'assign'])->name('sims.assign');
    Route::match(['get', 'post'], 'sims/return/{id}', [SimsController::class, 'return'])->name('sims.return');
    Route::get('sims/export', [SimsController::class, 'export'])->name('sims.export');
    Route::match(['get', 'post'], 'sims/import', [SimsController::class, 'import'])->name('sims.import');
    Route::get('sims/import_template', [SimsController::class, 'downloadTemplate'])->name('sims.import_template');

    Route::resource('sims', SimsController::class);
    Route::get('sims/delete/{id}', [SimsController::class, 'destroy'])->name('sims.delete');

    Route::get('simCompanies/trash', [SimCompaniesController::class, 'trash'])->name('simCompanies.trash');
    Route::post('simCompanies/trash/{id}/restore', [SimCompaniesController::class, 'restoreTrash'])->name('simCompanies.restore');
    Route::delete('simCompanies/trash/{id}/force-destroy', [SimCompaniesController::class, 'forceDestroyTrash'])->name('simCompanies.force-destroy');
    Route::resource('simCompanies', SimCompaniesController::class);
    Route::delete('simCompanies/delete/{id}', [SimCompaniesController::class, 'destroy'])->name('simCompanies.delete');
    Route::get('simInvoices', [SimInvoicesController::class, 'index'])->name('simInvoices.index');
    Route::get('simInvoices/create/{vendorId?}', [SimInvoicesController::class, 'create'])->name('simInvoices.create');
    Route::get('simInvoices/create-from-clone/{id}', [SimInvoicesController::class, 'createFromClone'])->name('simInvoices.createFromClone');
    Route::post('simInvoices/store', [SimInvoicesController::class, 'store'])->name('simInvoices.store');
    Route::get('simInvoices/{id}', [SimInvoicesController::class, 'show'])->name('simInvoices.show');
    Route::get('simInvoices/{id}/edit', [SimInvoicesController::class, 'edit'])->name('simInvoices.edit');
    Route::put('simInvoices/{id}', [SimInvoicesController::class, 'update'])->name('simInvoices.update');
    Route::delete('simInvoices/{id}', [SimInvoicesController::class, 'destroy'])->name('simInvoices.destroy');
    Route::post('simInvoices/{id}/clone', [SimInvoicesController::class, 'clone'])->name('simInvoices.clone');
    Route::get('simInvoices/vendor/{id}/sims', [SimInvoicesController::class, 'getSims'])->name('simInvoices.getSims');
    Route::get('sim/payments', [SimInvoicesController::class, 'payments'])->name('sim.payments');

    Route::get('bikeRentCompanies/trash', [BikeRentCompaniesController::class, 'trash'])->name('bikeRentCompanies.trash');
    Route::post('bikeRentCompanies/trash/{id}/restore', [BikeRentCompaniesController::class, 'restoreTrash'])->name('bikeRentCompanies.restore');
    Route::delete('bikeRentCompanies/trash/{id}/force-destroy', [BikeRentCompaniesController::class, 'forceDestroyTrash'])->name('bikeRentCompanies.force-destroy');
    Route::resource('bikeRentCompanies', BikeRentCompaniesController::class);
    Route::delete('bikeRentCompanies/delete/{id}', [BikeRentCompaniesController::class, 'destroy'])->name('bikeRentCompanies.delete');
    Route::get('bikeRentCompanies/ledger/{id}', [BikeRentCompaniesController::class, 'ledger'])->name('bikeRentCompanies.ledger');
    Route::get('bikeRentCompanies/files/{id}', [BikeRentCompaniesController::class, 'files'])->name('bikeRentCompanies.files');
    Route::get('bikeRentCompanies/invoices/{id}', [BikeRentCompaniesController::class, 'invoices'])->name('bikeRentCompanies.invoices');
    Route::get('bikeRentCompany/receipts', [BikeRentCompaniesController::class, 'allReceipts'])->name('bikeRentCompanies.all_receipts');
    Route::get('bikeRentCompanies/receipts/{id}', [BikeRentCompaniesController::class, 'receipts'])->name('bikeRentCompanies.receipts');
    Route::get('bikeRentCompanies/bikes/{id}', [BikeRentCompaniesController::class, 'bikes'])->name('bikeRentCompanies.bikes');
    Route::get('GarageCustomers/', [BikeRentCompaniesController::class, 'garageIndex'])->name('garage_customer.index');
    Route::get('GarageCustomers/ledger/{id}', [BikeRentCompaniesController::class, 'ledger'])->name('garage_customer.ledger');
    Route::get('GarageCustomers/files/{id}', [BikeRentCompaniesController::class, 'files'])->name('garage_customer.files');
    Route::get('GarageCustomers/receipts', [BikeRentCompaniesController::class, 'allReceipts'])->name('garage_customer.all_receipts');
    Route::get('GarageCustomers/receipts/{id}', [BikeRentCompaniesController::class, 'receipts'])->name('garage_customer.receipts');
    Route::get('GarageCustomers/bikes/{id}', [BikeRentCompaniesController::class, 'bikes'])->name('garage_customer.bikes');
    Route::get('GarageCustomers/maintenances/{id}', [BikeRentCompaniesController::class, 'maintenances'])->name('garage_customer.maintenances');
    /* Rider section starts from here */

    // Static rider paths must be registered before resource show (riders/{rider}).
    Route::get('riders/voucher-create', [RidersController::class, 'voucherCreate'])->name('riders.voucher.create');
    Route::resource('riders', RidersController::class);
    Route::post('riders/filter-ajax', [RidersController::class, 'filterAjax'])->name('riders.filterAjax');
    Route::get('riders/dropdown-options/modal', [RidersController::class, 'dropdownOptionModal'])->name('riders.dropdown-options.modal');
    Route::post('riders/dropdown-options', [RidersController::class, 'storeDropdownOption'])->name('riders.dropdown-options.store');
    Route::any('riders/job_status/{id?}', [RidersController::class, 'job_status'])->name('rider.job_status');

    Route::get('riders/timeline/{id?}', [RidersController::class, 'timeline'])->name('rider.timeline');
    Route::get('riders/history/{id}', [RidersController::class, 'history'])->name('rider.history');
    Route::get('riders/contract/{id?}', [RidersController::class, 'contract'])->name('rider.contract');
    Route::any('riders/contract_upload/{id?}', [RidersController::class, 'contract_upload'])->name('rider_contract_upload');
    Route::get('riders/{riderId}/agreements/modal', [App\Http\Controllers\AgreementGenerationController::class, 'modal'])->name('rider-agreements.modal');
    Route::get('riders/{riderId}/agreements/preview', [App\Http\Controllers\AgreementGenerationController::class, 'preview'])->name('rider-agreements.preview');
    Route::get('riders/{riderId}/agreements/pdf', [App\Http\Controllers\AgreementGenerationController::class, 'pdf'])->name('rider-agreements.pdf');
    Route::post('riders/{riderId}/agreements/email', [App\Http\Controllers\AgreementGenerationController::class, 'email'])->name('rider-agreements.email');
    Route::get('riders/{riderId}/agreements/templates/{template}/edit', [App\Http\Controllers\AgreementGenerationController::class, 'editTemplate'])->name('rider-agreements.templates.edit');
    Route::put('riders/{riderId}/agreements/templates/{template}', [App\Http\Controllers\AgreementGenerationController::class, 'updateTemplate'])->name('rider-agreements.templates.update');

    Route::any('riders/picture_upload/{id?}', [RidersController::class, 'picture_upload'])->name('rider_picture_upload');
    Route::any('riders/rider-document/{id}', [RidersController::class, 'document'])->name('rider.document');
    Route::get('riders/inventory/{id}', [RidersController::class, 'inventory'])->name('rider.inventory');
    Route::get('rider/updateRider', [RidersController::class, 'updateRider'])->name('rider.updateRider');
    Route::get('rider/delete/{id}', [RidersController::class, 'destroy'])->name('rider.delete');
    Route::get('riders/ledger/{id}', [RidersController::class, 'ledger'])->name('rider.ledger');
    Route::get('riders/attendance/{id}', [RidersController::class, 'attendance'])->name('rider.attendance');
    Route::get('riders/activities/{id}', [RidersController::class, 'activities'])->name('rider.activities');
    Route::get('riders/activities/{id}/pdf', [RidersController::class, 'activitiesPdf'])->name('riders.activities.pdf');
    Route::get('riders/activities/{id}/print', [RidersController::class, 'activitiesPrint'])->name('riders.activities.print');
    Route::get('riders/invoices/{id}', [RidersController::class, 'invoices'])->name('rider.invoices');
    Route::any('riders/sendemail/{id}', [RidersController::class, 'sendEmail'])->name('rider.sendemail');
    Route::get('riders/emails/{id}', [RidersController::class, 'emails'])->name('rider.emails');
    Route::get('rider/exportRiders', [RidersController::class, 'exportRiders'])->name('rider.exportRiders');
    Route::get('rider/exportCustomizableRiders', [RidersController::class, 'exportCustomizableRiders'])->name('rider.exportCustomizableRiders');

    // User Table Settings Routes
    Route::prefix('user-table-settings')->group(function () {
        Route::get('/', [UserTableSettingsController::class, 'getSettings'])->name('user-table-settings.get');
        Route::post('/', [UserTableSettingsController::class, 'saveSettings'])->name('user-table-settings.save');
        Route::delete('/', [UserTableSettingsController::class, 'resetSettings'])->name('user-table-settings.reset');
        Route::get('/all', [UserTableSettingsController::class, 'getAllSettings'])->name('user-table-settings.all');
    });
    Route::get('riders/files/{id}', [RidersController::class, 'files'])->name('rider.files');
    Route::get('riders/items/{id}', [RidersController::class, 'items'])->name('rider.items');
    Route::get('riders/additems/{id}', [RidersController::class, 'additems'])->name('riders.additems');
    Route::post('riders/storeitems/{id}', [RidersController::class, 'storeitems'])->name('riders.storeitems');
    Route::post('riders/{rider_id}/additem', [RidersController::class, 'additem'])->name('riders.additem');
    Route::get('riders/{rider_id}/edititem/{item_id}', [RidersController::class, 'edititem'])->name('riders.edititem');
    Route::post('riders/{rider_id}/updateitem/{item_id}', [RidersController::class, 'updateitem'])->name('riders.updateitem');
    Route::delete('riders/{rider_id}/deleteitem/{item_id}', [RidersController::class, 'deleteitem'])->name('riders.deleteitem');
    Route::get('riders/createitems/{id}', [RidersController::class, 'createitems'])->name('riders.createitems');
    Route::get('riders/visaloan/{id}', [RidersController::class, 'visaloan'])->name('riders.visaloan');
    Route::get('riders/advanceloan/{id}', [RidersController::class, 'advanceloan'])->name('riders.advanceloan');
    Route::get('riders/cod/{id}', [RidersController::class, 'cod'])->name('riders.cod');
    Route::get('riders/penalty/{id}', [RidersController::class, 'penalty'])->name('riders.penalty');
    Route::get('riders/incentive/{id}', [RidersController::class, 'incentive'])->name('riders.incentive');
    Route::get('riders/payment/{id}', [RidersController::class, 'payment'])->name('riders.payment');
    Route::get('rider/payments', [RidersController::class, 'payments'])->name('rider.payments');
    // Unified voucher modal (Advance Loan, COD, Penalty, Payment, Vendor Charges)
    Route::get('riders/voucher/{id}', [RidersController::class, 'voucher'])->name('riders.voucher');
    Route::post('riders/storevisaloan', [RidersController::class, 'storevisaloan'])->name('riders.storevisaloan');
    Route::post('riders/storecod', [RidersController::class, 'storecod'])->name('riders.storecod');
    Route::post('riders/storepenalty', [RidersController::class, 'storepenalty'])->name('riders.storepenalty');
    Route::post('riders/storeincentive', [RidersController::class, 'storeincentive'])->name('riders.storeincentive');
    Route::post('riders/storepayment', [RidersController::class, 'storepayment'])->name('riders.storepayment');
    // Riders vouchers import (modal - existing)
    Route::any('rider/voucher-import', [RidersController::class, 'importVouchers'])->name('riders.voucher_import');
    // Standalone Import Rider Vouchers page
    Route::match(['get', 'post'], 'rider/import-rider-vouchers', [RidersController::class, 'importRiderVouchers'])
        ->name('riders.import_rider_vouchers');
    Route::post('riders/storeadvanceloan', [RidersController::class, 'storeadvanceloan'])->name('riders.storeadvanceloan');
    Route::post('riders/update-section/{id}', [RidersController::class, 'updateSection'])->name('riders.updateSection');
    Route::post('riders/set-rider-top-option/{id}', [RidersController::class, 'setRiderTopOption'])->name('riders.setRiderTopOption');
    Route::post('riders/return-bike/{id}', [RidersController::class, 'returnBike'])->name('riders.returnBike');
    Route::post('riders/add-recruiter', [RidersController::class, 'addRecruiter'])->name('riders.addRecruiter');
    Route::get('riders/vendorcharges/{id}', [RidersController::class, 'vendorcharges'])->name('riders.vendorcharges');
    Route::post('riders/storevendorcharges', [RidersController::class, 'storevendorcharges'])->name('riders.storevendorcharges');

    Route::resource('riderleads', riderhiringController::class);

    Route::get('payments/{id}/clone', [PaymentController::class, 'clone'])->name('payments.clone');
    Route::resource('payments', PaymentController::class);
    Route::get('receipts/{id}/clone', [ReceiptController::class, 'clone'])->name('receipts.clone');
    Route::resource('receipts', ReceiptController::class);

    Route::get('riders/file-manager', function () {
        return view('riders.file-manager');
    })->name('rider.file-manager');

    Route::resource('riderEmails', RiderEmailsController::class);

    Route::any('riderInvoices/sendemail/{id}', [RiderInvoicesController::class, 'sendEmail'])->name('riderInvoices.sendEmail');
    Route::get('riderInvoices/{id}/download', [RiderInvoicesController::class, 'download'])->name('riderInvoices.download');
    Route::post('riderInvoices/{id}/template', [RiderInvoicesController::class, 'updateTemplate'])->name('riderInvoices.updateTemplate');
    Route::resource('riderInvoices', RiderInvoicesController::class);
    Route::any('rider/invoice-import', [RiderInvoicesController::class, 'import'])->name('rider.invoice_import');
    Route::any('rider/invoice-import-paid', [RiderInvoicesController::class, 'importPaid'])->name('riderInvoices.importPaid');
    Route::any('rider/invoice-mark-paid/{id}', [RiderInvoicesController::class, 'markAsPaid'])->name('riderInvoices.markAsPaid');
    Route::get('search_item_price/{RID}/{itemID}', [ItemsController::class, 'search_item_price']);
    Route::get('riderInvoices/delete/{id}', [RiderInvoicesController::class, 'destroy'])->name('riderInvoices.delete');
    Route::post('riderInvoices/bulk-delete', [RiderInvoicesController::class, 'bulkDelete'])->name('riderInvoices.bulkDelete');
    Route::resource('employeeInvoices', EmployeeInvoicesController::class);
    Route::get('employeeInvoices/delete/{id}', [EmployeeInvoicesController::class, 'destroy'])->name('employeeInvoices.delete');
    Route::post('employeeInvoices/bulk-delete', [EmployeeInvoicesController::class, 'bulkDelete'])->name('employeeInvoices.bulkDelete');

    Route::resource('riderAttendances', RiderAttendanceController::class);
    Route::any('rider/attendance-import', [RiderAttendanceController::class, 'import'])->name('rider.attendance_import');

    Route::resource('riderActivities', RiderActivitiesController::class);
    Route::any('rider/activities-import', [RiderActivitiesController::class, 'import'])->name('rider.activities_import');
    Route::any('rider/keeta-activities-import', [RiderActivitiesController::class, 'importKeeta'])->name('rider.keeta_activities_import');
    Route::get('rider/activities-import/errors', [RiderActivitiesController::class, 'importErrors'])->name('rider.activities_import_errors');

    Route::get('rider/riderliveActivities', [RiderActivitiesController::class, 'liveactivities'])->name('rider.liveactivities');
    Route::any('rider/live-activities-import', [RiderActivitiesController::class, 'liveimportactivities'])->name('rider.live_activities_import');
    Route::get('rider/live-activities-import/errors', [RiderActivitiesController::class, 'liveimportErrors'])->name('rider.live_activities_import_errors');
    /* Rider section end here */

    Route::resource('supplier_invoices', SupplierInvoicesController::class);
    Route::get('supplierInvoices/delete/{id}', [SupplierInvoicesController::class, 'destroy'])->name('supplierInvoices.delete');

    Route::get('/item/{id}/price', [ItemsController::class, 'getPrice'])->name('item.price');

    Route::get('/get-item-price/{id}', [ItemsController::class, 'getItemPrice'])->name('item.getPrice');
    Route::get('items/delete/{id}', [ItemsController::class, 'destroy'])->name('items.delete');
    Route::any('items/get-owners', [ItemsController::class, 'getOwners'])->name('items.get-owners');
    Route::any('items/get-rider-items/{rider_id}', [ItemsController::class, 'itemsByRider'])->name('items.rider');

    Route::resource('files', FilesController::class);

    Route::resource('vendors', VendorsController::class);

    Route::get('vendors/delete/{id}', [VendorsController::class, 'destroy'])->name('vendors.delete');
    // Vendors Trash Routes
    Route::get('vendors/trash', [VendorsController::class, 'trash'])->name('vendors.trash');
    Route::post('vendors/trash/{id}/restore', [VendorsController::class, 'restoreTrash'])->name('vendors.restore');
    Route::delete('vendors/trash/{id}/force-destroy', [VendorsController::class, 'forceDestroyTrash'])->name('vendors.force-destroy');

    Route::resource('recruiters', RecruitersController::class);
    Route::get('recruiters/{recruiter}/riders', [RecruitersController::class, 'showRiders'])->name('recruiters.riders');
    Route::delete('recruiters/delete/{id}', [RecruitersController::class, 'destroy'])->name('recruiters.delete');
    Route::get('recruiters/trash', [RecruitersController::class, 'trash'])->name('recruiters.trash');
    Route::post('recruiters/trash/{id}/restore', [RecruitersController::class, 'restoreTrash'])->name('recruiters.restore');
    Route::delete('recruiters/trash/{id}/force-destroy', [RecruitersController::class, 'forceDestroyTrash'])->name('recruiters.force-destroy');
    Route::get('recruiters/{recruiter}/assign-riders', [RecruitersController::class, 'showAssignRidersView'])->name('recruiters.assign-riders');
    Route::post('recruiters/{recruiter}/assign-riders', [RecruitersController::class, 'assignRiders'])->name('recruiters.assign-riders.store');
    Route::get('recruiters/unassigned-riders', [RecruitersController::class, 'getUnassignedRiders'])->name('recruiters.unassigned-riders');
    Route::post('recruiters/{recruiter}/remove-riders', [RecruitersController::class, 'removeRiders'])->name('recruiters.remove-riders');

    Route::resource('bikeHistories', BikeHistoryController::class);

    Route::resource('simHistories', SimHistoryController::class);
    Route::any('fuel_transactions/import', [FuelDataController::class, 'import'])->name('fuel_data.import');
    Route::get('fuel_transactions/importSample', [FuelDataController::class, 'downloadTemplate'])->name('fuel_data.importSample');
    Route::get('fuel_data/summary', [FuelDataController::class, 'monthlySummary'])->name('fuel_data.summary');
    Route::get('fuel_invoice/{rider_id}/{billing_month}', [FuelDataController::class, 'show2'])->name('fuel_data.rider_monthly_summary');
    Route::get('fuel_data/delete-monthly', [FuelDataController::class, 'deleteMonthlyForm'])->name('fuel_data.deleteMonthlyForm');
    Route::post('fuel_data/delete-monthly', [FuelDataController::class, 'deleteMonthly'])->name('fuel_data.deleteMonthly');
    Route::resource('fuel_data', FuelDataController::class);

    Route::get('fuelCompanies/trash', [FuelCompaniesController::class, 'trash'])->name('fuelCompanies.trash');
    Route::post('fuelCompanies/trash/{id}/restore', [FuelCompaniesController::class, 'restoreTrash'])->name('fuelCompanies.restore');
    Route::delete('fuelCompanies/trash/{id}/force-destroy', [FuelCompaniesController::class, 'forceDestroyTrash'])->name('fuelCompanies.force-destroy');
    Route::get('fuelCompanies/top-up/create', [FuelCompaniesController::class, 'createTopUp'])->name('fuelCompanies.topUp.create');
    Route::post('fuelCompanies/top-up', [FuelCompaniesController::class, 'storeTopUp'])->name('fuelCompanies.topUp.store');
    Route::resource('fuelCompanies', FuelCompaniesController::class);
    Route::delete('fuelCompanies/delete/{id}', [FuelCompaniesController::class, 'destroy'])->name('fuelCompanies.delete');

    Route::any('inventory/showBatch/{batch_no}', [InventoryPurchaseController::class, 'showBatch'])->name('inventory.showBatch');
    Route::any('inventory/purchase/history', [InventoryPurchaseController::class, 'indexBatches'])->name('inventory.indexBatch');
    Route::resource('inventory', InventoryPurchaseController::class);

    Route::resource('fuelCards', FuelCardController::class);
    Route::any('fuelcards/import', [FuelCardController::class, 'import'])->name('fuelCards.import');
    Route::get('fuelcards/import_template', [FuelCardController::class, 'downloadTemplate'])->name('fuelCards.import_template');
    Route::get('fuelcards/export', [FuelCardController::class, 'export'])->name('fuelCards.export');

    Route::any('fuelcards/assign/{id}', [FuelCardHistoryController::class, 'assign'])->name('fuelCards.assign');
    Route::any('fuelcards/return/{id}', [FuelCardHistoryController::class, 'return'])->name('fuelCards.return');
    Route::any('fuelcards/update_assignment/{id}', [FuelCardHistoryController::class, 'updateAssignment'])->name('fuelCards.update_assignment');

    Route::resource('leasingCompanies', LeasingCompaniesController::class);
    Route::delete('leasingCompanies/delete/{id}', [LeasingCompaniesController::class, 'destroy'])->name('leasingCompanies.delete');
    Route::get('leasingCompanies/bikes/{id}', [LeasingCompaniesController::class, 'bikes'])->name('leasingCompany.bikes');
    // Leasing Companies Trash Routes
    Route::get('leasingCompanies/trash', [LeasingCompaniesController::class, 'trash'])->name('leasingCompanies.trash');
    Route::post('leasingCompanies/trash/{id}/restore', [LeasingCompaniesController::class, 'restoreTrash'])->name('leasingCompanies.restore');
    Route::delete('leasingCompanies/trash/{id}/force-destroy', [LeasingCompaniesController::class, 'forceDestroyTrash'])->name('leasingCompanies.force-destroy');

    // Leasing Company Invoice Routes
    Route::get('leasingCompanyInvoices', [LeasingCompaniesController::class, 'indexInvoices'])->name('leasingCompanyInvoices.index');
    Route::get('leasingCompanyInvoices/create/{leasingCompanyId?}', [LeasingCompaniesController::class, 'createInvoice'])->name('leasingCompanyInvoices.create');
    Route::get('leasingCompanyInvoices/create-from-clone/{id}', [LeasingCompaniesController::class, 'createFromClone'])->name('leasingCompanyInvoices.createFromClone');
    Route::post('leasingCompanyInvoices/store', [LeasingCompaniesController::class, 'storeInvoice'])->name('leasingCompanyInvoices.store');
    Route::get('leasingCompanyInvoices/{id}', [LeasingCompaniesController::class, 'showInvoice'])->name('leasingCompanyInvoices.show');
    Route::get('leasingCompanyInvoices/{id}/edit', [LeasingCompaniesController::class, 'editInvoice'])->name('leasingCompanyInvoices.edit');
    Route::put('leasingCompanyInvoices/{id}', [LeasingCompaniesController::class, 'updateInvoice'])->name('leasingCompanyInvoices.update');
    Route::delete('leasingCompanyInvoices/{id}', [LeasingCompaniesController::class, 'destroyInvoice'])->name('leasingCompanyInvoices.destroy');
    Route::post('leasingCompanyInvoices/{id}/clone', [LeasingCompaniesController::class, 'cloneInvoice'])->name('leasingCompanyInvoices.clone');

    // Leasing Company Billing Invoice Routes (separate module)
    Route::get('leasingCompanyBillingInvoices', [LeasingCompanyBillingInvoicesController::class, 'index'])->name('leasingCompanyBillingInvoices.index');
    Route::get('leasingCompanyBillingInvoices/create/{customerId?}', [LeasingCompanyBillingInvoicesController::class, 'create'])->name('leasingCompanyBillingInvoices.create');
    Route::get('leasingCompanyBillingInvoices/create-from-clone/{id}', [LeasingCompanyBillingInvoicesController::class, 'createFromClone'])->name('leasingCompanyBillingInvoices.createFromClone');
    Route::post('leasingCompanyBillingInvoices/store', [LeasingCompanyBillingInvoicesController::class, 'store'])->name('leasingCompanyBillingInvoices.store');
    Route::get('leasingCompanyBillingInvoices/{id}', [LeasingCompanyBillingInvoicesController::class, 'show'])->name('leasingCompanyBillingInvoices.show');
    Route::get('leasingCompanyBillingInvoices/{id}/edit', [LeasingCompanyBillingInvoicesController::class, 'edit'])->name('leasingCompanyBillingInvoices.edit');
    Route::put('leasingCompanyBillingInvoices/{id}', [LeasingCompanyBillingInvoicesController::class, 'update'])->name('leasingCompanyBillingInvoices.update');
    Route::delete('leasingCompanyBillingInvoices/{id}', [LeasingCompanyBillingInvoicesController::class, 'destroy'])->name('leasingCompanyBillingInvoices.destroy');
    Route::post('leasingCompanyBillingInvoices/{id}/clone', [LeasingCompanyBillingInvoicesController::class, 'clone'])->name('leasingCompanyBillingInvoices.clone');
    Route::get('leasingCompanies/{id}/invoices', [LeasingCompaniesController::class, 'createInvoice'])->name('leasingCompanies.createInvoice');
    Route::post('leasingCompanies/{id}/invoices', [LeasingCompaniesController::class, 'storeInvoice'])->name('leasingCompanies.storeInvoice');
    Route::get('leasingCompanies/{id}/bikes', [LeasingCompaniesController::class, 'getBikes'])->name('leasingCompanies.getBikes');
    Route::get('leasingCompanies/receipts/{id}', [LeasingCompaniesController::class, 'receipts'])->name('leasingCompanies.receipts');
    Route::get('leasingCompany/receipts', [LeasingCompaniesController::class, 'receipt'])->name('leasingCompanies.receipt');
    Route::get('leasingCompany/payments', [LeasingCompaniesController::class, 'payment'])->name('leasingCompanies.payment');
    Route::get('leasingCompanies/payments/{id}', [LeasingCompaniesController::class, 'payments'])->name('leasingCompanies.payments');
    Route::get('leasingCompany/files/{id}', [LeasingCompaniesController::class, 'files'])->name('leasingCompany.files');
    Route::get('leasingCompany/ledger/{id}', [LeasingCompaniesController::class, 'ledger'])->name('leasingCompany.ledger');
    Route::resource('garages', GaragesController::class);
    Route::get('garages/delete/{id}', [GaragesController::class, 'destroy'])->name('garages.delete');

    Route::resource('banks', BanksController::class);
    Route::get('banks/ledger/{id}', [BanksController::class, 'ledger'])->name('bank.ledger');
    Route::get('banks/files/{id}', [BanksController::class, 'files'])->name('bank.files');
    Route::get('banks/delete/{id}', [BanksController::class, 'destroy'])->name('bank.delete');
    Route::get('banks/receipts/{id}', [BanksController::class, 'receipts'])->name('banks.receipts');
    Route::get('banks/payments/{id}', [BanksController::class, 'payments'])->name('banks.payments');
    Route::get('banks/cheques/{id}', [BanksController::class, 'cheques'])->name('banks.cheques');

    Route::get('loans/upcoming-installments', [LoansController::class, 'upcomingInstallments'])->name('loans.upcomingInstallments');
    Route::get('loans/interest-summary', [LoansController::class, 'interestSummary'])->name('loans.interestSummary');
    Route::get('loans/trash', [LoansController::class, 'trash'])->name('loans.trash');
    Route::resource('loans', LoansController::class);
    Route::post('loans/{id}/disburse', [LoansController::class, 'disburse'])->name('loans.disburse');
    Route::get('loans/{id}/installments', [LoansController::class, 'installments'])->name('loans.installments');
    Route::get('loanInstallments/{id}/pay', [LoansController::class, 'payInstallmentForm'])->name('loanInstallments.payForm');
    Route::post('loanInstallments/{id}/pay', [LoansController::class, 'payInstallment'])->name('loanInstallments.pay');
    Route::post('loans/{id}/regenerate-schedule', [LoansController::class, 'regenerateScheduleAction'])->name('loans.regenerateSchedule');
    Route::get('loan/files/{id}', [LoansController::class, 'files'])->name('loan.files');
    Route::get('loans/ledger/{id}', [LoansController::class, 'ledger'])->name('loans.ledger');
    Route::post('loans/trash/{id}/restore', [LoansController::class, 'restoreTrash'])->name('loans.restore');
    Route::delete('loans/trash/{id}/force-destroy', [LoansController::class, 'forceDestroyTrash'])->name('loans.force-destroy');

    Route::post('/cheques/status/{id}', [ChequesController::class, 'updateStatus'])->name('cheques.update-status');
    Route::get('cheques/change_status/{id}', [ChequesController::class, 'statusForm'])->name('cheques.status-form');
    Route::post('cheques/set-cheque-top-option/{id}', [ChequesController::class, 'setChequeTopOption'])->name('cheques.setChequeTopOption');
    Route::resource('cheques', ChequesController::class);

    Route::get('vouchers/{id}/clone', [VouchersController::class, 'cloneVoucher'])->name('vouchers.clone');
    Route::get('vouchers/list-sidebar', [VouchersController::class, 'listSidebar'])->name('vouchers.list-sidebar');
    Route::resource('vouchers', VouchersController::class);
    Route::any('voucher/import', [VouchersController::class, 'import'])->name('voucher.import');
    Route::get('get_invoice_balance', [VouchersController::class, 'GetInvoiceBalance'])->name('get_invoice_balance');
    Route::get('fetch_invoices/{id}/{vt}', [VouchersController::class, 'fetch_invoices']);
    /*   Route::any('attach_file/{id}', 'VouchersController@fileUpload'); */
    Route::any('voucher/attach_file/{id}', [VouchersController::class, 'fileUpload'])->name('voucher.fileupload');

    Route::prefix('settings')->group(function () {

        Route::any('/company', [HomeController::class, 'settings'])->name('settings');
        Route::get('/erp', [ErpSettingsController::class, 'index'])->name('settings.erp');
        Route::post('/erp', [ErpSettingsController::class, 'store'])->name('settings.erp.store');
        Route::resource('departments', DepartmentsController::class);
        Route::resource('dropdowns', DropdownsController::class);
    });
    Route::prefix('reports')->group(function () {
        Route::get('/rider_report', [ReportController::class, 'rider_report'])->name('reports.rider_report');
        Route::post('/rider_report_data', [ReportController::class, 'rider_report_data'])->name('reports.rider_report_data');
        Route::get('/rider_report/detail/{rider}', [ReportController::class, 'rider_report_detail'])->name('reports.rider_report_detail');
        Route::get('/rider_monthly_report', [ReportController::class, 'rider_monthly_report'])->name('reports.rider_monthly_report');
        Route::post('/rider_monthly_report_data', [ReportController::class, 'rider_monthly_report_data'])->name('reports.rider_monthly_report_data');
    });

    Route::get('/itmeslist', function () {
        return General::dropdownitems();
    });

    Route::prefix('accounts')->group(function () {

        Route::get('detail/{id}', [AccountsController::class, 'accountDetail'])->name('accounts.detail');
        Route::get('detail/{id}/ledger-entries', [AccountsController::class, 'ledgerEntries'])->name('accounts.ledgerEntries');
        Route::resource('accounts', AccountsController::class)->parameters(['accounts' => 'id']);
        Route::get('tree', [AccountsController::class, 'tree'])->name('accounts.tree');
        // Accounts Trash Routes
        Route::get('trash', [AccountsController::class, 'trash'])->name('accounts.trash');
        Route::post('trash/{id}/restore', [AccountsController::class, 'restoreTrash'])->name('accounts.restore');
        Route::delete('trash/{id}/force-destroy', [AccountsController::class, 'forceDestroyTrash'])->name('accounts.force-destroy');

        Route::get('/ledgerreport', [LedgerController::class, 'ledger'])->name('accounts.ledgerreport');
        Route::get('/ledger', [LedgerController::class, 'index'])->name('accounts.ledger');
        Route::get('/ledger/data', [LedgerController::class, 'getLedgerData'])->name('ledger.data');
        Route::get('/ledger/export', [LedgerController::class, 'export'])->name('ledger.export');
        Route::get('/ledger/print', [LedgerController::class, 'print'])->name('ledger.print');
        Route::get('/reports/trial-balance', [AccountsReportController::class, 'trialBalance'])->name('accounts.reports.trial_balance');
        Route::get('/reports/profit-loss', [AccountsReportController::class, 'profitLoss'])->name('accounts.reports.profit_loss');
        Route::get('/vat', [VatController::class, 'index'])->name('vat.index');
        Route::get('/vat/returns', [VatController::class, 'returnsIndex'])->name('vat.returns.index');
        Route::get('/vat/returns/{vat_return}', [VatController::class, 'returnsShow'])->name('vat.returns.show');
        Route::post('/vat/return-file', [VatController::class, 'fileReturn'])->name('vat.return.file');
        Route::patch('/vat/returns/{vat_return}/status', [VatController::class, 'updateReturnStatus'])->name('vat.returns.update-status');
        Route::delete('/vat/returns/{vat_return}', [VatController::class, 'destroyReturn'])->name('vat.returns.destroy');
        Route::post('/vat/returns/{vat_return}/delete-entries', [VatController::class, 'deleteReturnEntries'])->name('vat.returns.delete-entries');
        Route::get('/vat/voucher/create', [VatController::class, 'createVoucher'])->name('vat.voucher.create');
        Route::post('/vat/voucher/store', [VatController::class, 'storeVoucher'])->name('vat.voucher.store');
        Route::post('accounts/{id}/toggle-lock', [AccountsController::class, 'toggleLock'])->name('accounts.toggleLock');
        Route::post('accounts/{id}/toggle-status', [AccountsController::class, 'toggleStatus'])->name('accounts.toggleStatus');
        Route::post('accounts/{id}/toggle-fixed', [AccountsController::class, 'toggleFixed'])->name('accounts.toggleFixed');
    });

    // Expense module: expense accounts from Chart of Accounts
    Route::get('expenses/detail/{id}', [ExpenseController::class, 'accountDetail'])->name('expenses.detail');
    Route::get('expenses/detail/{id}/ledger-entries', [ExpenseController::class, 'ledgerEntries'])->name('expenses.ledgerEntries');
    Route::post('expenses/{id}/toggle-lock', [ExpenseController::class, 'toggleLock'])->name('expenses.toggleLock');
    Route::post('expenses/{id}/toggle-status', [ExpenseController::class, 'toggleStatus'])->name('expenses.toggleStatus');
    Route::get('expenses/voucher/create', [ExpenseController::class, 'createVoucher'])->name('expenses.voucher.create');
    Route::post('expenses/voucher/store', [ExpenseController::class, 'storeVoucher'])->name('expenses.voucher.store');
    Route::get('expenses/voucher/{id}/edit', [ExpenseController::class, 'editVoucher'])->name('expenses.voucher.edit');
    Route::put('expenses/voucher/{id}', [ExpenseController::class, 'updateVoucher'])->name('expenses.voucher.update');
    Route::delete('expenses/voucher/{id}', [ExpenseController::class, 'destroyVoucher'])->name('expenses.voucher.destroy');
    Route::get('expenses/list-sidebar', [ExpenseController::class, 'listSidebar'])->name('expenses.list-sidebar');
    Route::get('expenses/voucher/{id}', [ExpenseController::class, 'showVoucher'])->name('expenses.voucher.show');
    Route::resource('expenses', ExpenseController::class)->only(['index', 'create', 'store']);
});
Route::group(['prefix' => 'laravel-filemanager', 'middleware' => ['web', 'auth']], function () {
    Lfm::routes();
});
/* Route::group(['prefix' => 'laravel-filemanager', 'middleware' => ['web', 'auth']], function () {
  Lfm::routes();
}); */

Route::get('/artisan-cache', function () {
    Artisan::call('cache:clear');

    return 'cache cleared';
});
Route::get('/artisan-route', function () {
    Artisan::call('route:clear');

    return 'ruote cleared';
});

Route::get('/artisan-optimize', function () {
    Artisan::call('optimize');

    return 'optimized';
});
Route::get('/artisan-optimize-clear', function () {
    Artisan::call('optimize:clear');

    return 'optimized';
});
Route::get('/artisan-storage-link', function () {
    Artisan::call('storage:link');

    return 'storage link';
});

Route::get('/artisan-storage-unlink', function () {
    Artisan::call('storage:unlink');

    return 'storage unlink';
});

// Admin tables: only migrations in database/migrations_admin (or one file via ?path=...)
Route::get('/run-admin-migrate', function () {
    // Web SAPI often has memory_limit=512M; migrations need more headroom than HTTP defaults.
    if (@ini_get('memory_limit') !== '-1') {
        @ini_set('memory_limit', '1024M');
    }

    $path = request('path');
    $options = [
        '--database' => 'mysql_admin',
        '--force' => true,
    ];

    if ($path !== null && $path !== '') {
        $path = str_replace('\\', '/', (string) $path);
        if (str_contains($path, '..')) {
            return response('Invalid path.', 400);
        }
        if (! str_starts_with($path, 'database/')) {
            return response('path must start with database/ (e.g. database/migrations_admin/2026_03_20_000004_create_admin_auth_and_permission_tables.php)', 400);
        }
        $full = realpath(base_path($path));
        if ($full === false || ! str_starts_with($full, realpath(base_path('database')))) {
            return response('path must be under database/.', 400);
        }
        $options['--path'] = $path;
    } else {
        $options['--path'] = 'database/migrations_admin';
    }

    Artisan::call('migrate', $options);

    return Artisan::output();
});

/* Route::resource('calculations', App\Http\Controllers\CalculationsController::class)
    ->names([
        'index' => 'calculations.index',
        'store' => 'calculations.store',
        'show' => 'calculations.show',
        'update' => 'calculations.update',
        'destroy' => 'calculations.destroy',
        'create' => 'calculations.create',
        'edit' => 'calculations.edit'
    ]); */

/* Settings section end here */
/* Legacy non-tenant settings routes removed — use app/{company_slug}/settings/* instead. */

/* Suppliers section start here */
Route::prefix('app/{company_slug}')->middleware(['web', 'tenant', 'company.routes', 'auth'])->group(function () {
    // Suppliers: explicit routes before resource (avoid clashes with {supplier})
    Route::get('suppliers/trash', [SupplierController::class, 'trash'])->name('suppliers.trash');
    Route::post('suppliers/trash/{id}/restore', [SupplierController::class, 'restoreTrash'])->name('suppliers.restore');
    Route::delete('suppliers/trash/{id}/force-destroy', [SupplierController::class, 'forceDestroyTrash'])->name('suppliers.force-destroy');
    Route::get('suppliers/document/{id}', [SupplierController::class, 'document'])->name('suppliers.document');
    Route::get('suppliers/ledger/{id}', [SupplierController::class, 'ledger'])->name('suppliers.ledger');
    Route::delete('suppliers/delete/{id}', [SupplierController::class, 'destroy'])->name('suppliers.delete');
    Route::get('suppliers/show/{id}', [SupplierController::class, 'show']);
    Route::resource('suppliers', SupplierController::class);

    // Supplier invoices (custom URL patterns; exclude overlapping resource actions)
    Route::resource('supplierInvoices', SupplierInvoicesController::class)->except(['show', 'edit', 'update', 'create', 'store']);
    Route::get('supplier/purchase_orders', [SupplierInvoicesController::class, 'purchaseOrders'])->name('supplier.purchase_order');
    Route::get('supplier/payments', [SupplierInvoicesController::class, 'payments'])->name('supplier.payments');
    Route::any('/supplier_invoices/import', [SupplierInvoicesController::class, 'import'])->name('supplier_invoices.import');
    Route::post('/supplier/invoice/import', [SupplierInvoicesController::class, 'import'])->name('supplier.invoice_import');
    Route::get('/supplier/ledger', [SupplierInvoicesController::class, 'ledger'])->name('supplier.ledger');
    Route::post('/supplier_invoices/send-email/{id}', [SupplierInvoicesController::class, 'sendEmail'])->name('supplier_invoices.send_email');
    Route::get('supplierInvoices/edit/{id}', [SupplierInvoicesController::class, 'edit'])->name('supplierInvoices.edit');
    Route::put('/supplierInvoices/{id}', [SupplierInvoicesController::class, 'update'])->name('supplierInvoices.update');
    Route::get('/supplier_invoices/{id}', [SupplierInvoicesController::class, 'show'])->name('supplierInvoices.show');
    Route::get('/supplierInvoices/create', [SupplierInvoicesController::class, 'create'])->name('supplierInvoices.create');
    Route::post('supplierInvoices', [SupplierInvoicesController::class, 'store'])->name('supplierInvoices.store');
});

/* Suppliers section end here */
Route::prefix('app/{company_slug}')->middleware(['web', 'tenant', 'company.routes', 'auth'])->group(function () {
    // Specific Salik routes (must come before resource route)
    Route::get('salik/missing-records', [SalikController::class, 'showMissingRecords'])->name('salik.missing.records');
    Route::get('salik/summary', [SalikController::class, 'monthlySummary'])->name('salik.summary');
    Route::get('salik/payments', [SalikController::class, 'paymentRecords'])->name('salik.payments');
    Route::get('salik_invoice/{rider_id}/{billing_month}', [SalikController::class, 'showMonthlyInvoice'])->name('salik.rider_monthly_summary');
    Route::get('salik_invoice/company/{rental_company_id}/{billing_month}', [SalikController::class, 'showCompanyMonthlyInvoice'])->name('salik.company_monthly_summary');
    Route::get('salik/export-missing-records', [SalikController::class, 'exportMissingRecords'])->name('salik.export.missing.records');
    Route::post('salik/analyze-excel', [SalikController::class, 'analyzeExcelFile'])->name('salik.analyze.excel');
    Route::any('salik/clear-failed-imports', [SalikController::class, 'clearFailedImports'])->name('salik.clear.failed.imports');
    Route::get('salik/import-template', [SalikController::class, 'downloadTemplate'])->name('salik.import_template');
    Route::get('salik/import', [SalikController::class, 'importForm'])->name('salik.import.form');
    Route::get('salik/import/{salik_account_id}', [SalikController::class, 'importForm'])->name('salik.import.form.legacy');
    Route::post('salik/import', [SalikController::class, 'import'])->name('salik.import');
    Route::post('salik/test-import', [SalikController::class, 'testImport'])->name('salik.test.import');
    Route::get('salik/top-up/create', [SalikController::class, 'createTopUp'])->name('salik.topUp.create');
    Route::post('salik/top-up', [SalikController::class, 'storeTopUp'])->name('salik.topUp.store');
    Route::get('salik/payment', [SalikController::class, 'paymentForm'])->name('salik.payment');
    Route::post('salik/payment/records', [SalikController::class, 'getPaymentRecords'])->name('salik.payment.records');
    Route::post('salik/payment/calculate', [SalikController::class, 'calculatePaymentVoucher'])->name('salik.payment.calculate');
    Route::post('salik/payment/store', [SalikController::class, 'storePayment'])->name('salik.payment.store');
    Route::get('salik/delete-monthly', [SalikController::class, 'deleteMonthlyForm'])->name('salik.deleteMonthlyForm');
    Route::post('salik/delete-monthly', [SalikController::class, 'deleteMonthly'])->name('salik.deleteMonthly');

    // Static path segments must be registered before salik/{salik} (resource show)
    Route::get('salik/create', [SalikController::class, 'create'])->name('salik.create');
    Route::get('salik/create/{id}', [SalikController::class, 'create'])->name('salik.create.legacy');
    Route::post('salik/store', [SalikController::class, 'store'])->name('salik.store');
    Route::get('salik/edit/{id}', [SalikController::class, 'edit'])->name('salik.edit');
    Route::post('/salik/{id}/update', [SalikController::class, 'update'])->name('salik.update');
    Route::any('salik/attach_file/{id}', [SalikController::class, 'fileUpload'])->name('salik.fileupload');
    Route::get('salik/delete/{id}', [SalikController::class, 'destroy'])->name('salik.delete');
    Route::get('salik/tickets/{id?}', [SalikController::class, 'tickets'])->name('salik.tickets');
    Route::get('salik/viewvoucher/{id}', [SalikController::class, 'viewvoucher'])->name('salik.viewvoucher');
    Route::post('salik/getriderbybikedate', [SalikController::class, 'getriderbybikedate'])->name('salik.getriderbybikedate');

    // Salik resource routes (legacy custom URLs kept for existing links)
    Route::resource('salik', SalikController::class)->except(['store', 'edit', 'update', 'create', 'destroy']);
});
