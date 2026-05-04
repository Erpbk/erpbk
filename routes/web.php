<?php

use App\Http\Controllers\BikesController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\FilesController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ItemsController;
use App\Http\Controllers\LedgerController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\SupplierInvoicesController;
use App\Http\Controllers\UploadFilesController;
use App\Http\Controllers\VouchersController;
use App\Http\Controllers\VisaexpenseController;
use App\Http\Controllers\VisaStatusController;
use App\Http\Controllers\SalikController;
use App\Http\Controllers\riderhiringController;
use App\Http\Controllers\ActivityLogController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\pages\HomePage;
use App\Http\Controllers\pages\Page2;
use App\Http\Controllers\pages\MiscError;
use App\Http\Controllers\authentications\LoginBasic;
use App\Http\Controllers\authentications\RegisterBasic;
use App\Http\Controllers\RecruitersController;
use App\Http\Controllers\Company\CompanyAuthController;
use App\Http\Controllers\Company\CompanyRegistrationController;
use App\Http\Controllers\Admin\AdminCompaniesController;
use App\Http\Controllers\Admin\AdminBlogsController;
use App\Http\Controllers\Admin\AdminTestimonialsController;
use App\Http\Controllers\Admin\AdminPolicyController;
use App\Http\Controllers\Admin\AdminUsersController;
use App\Http\Controllers\Admin\AdminRolesController;
use App\Http\Controllers\Admin\AdminLoginController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminPermissionsController;
use App\Http\Controllers\Admin\AdminAccountFixingController;
use Illuminate\Support\Facades\Artisan;


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

// ---------- Company login (public): find tenant by name, then slug login ----------
Route::redirect('/', '/company/login');
Route::get('company/login', [CompanyAuthController::class, 'showFindLoginForm'])->name('company.find-login');
Route::post('company/login', [CompanyAuthController::class, 'findLogin'])->name('company.find-login.submit');

Route::get('app/{company_slug}/login', [CompanyAuthController::class, 'showLoginForm'])->name('company.login-form');
Route::post('app/{company_slug}/login', [CompanyAuthController::class, 'login'])->name('company.login');

// ---------- Admin login (separate portal) ----------
Route::get('admin/login', [AdminLoginController::class, 'showLogin'])->name('admin.login')->middleware('guest:admin');
Route::post('admin/login', [AdminLoginController::class, 'login'])->name('admin.login.submit')->middleware('guest:admin');
Route::post('admin/logout', [AdminLoginController::class, 'logout'])->name('admin.logout')->middleware('auth:admin');

// Tenant UI lives in one Route::prefix('app/{company_slug}') group below. Avoid a second group with the same prefix (duplicate URIs break route names / matching).

// Settings panel must live under /app/{company_slug}/ so company context is active (same names: settings-panel.*)
Route::prefix('app/{company_slug}')->middleware(['web', 'company.routes', 'tenant', 'auth'])->group(function () {
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

    // Account fixing (global chart account sharing)
    Route::get('accounts/fixed', [AdminAccountFixingController::class, 'index'])->name('accounts.fixed.index');
    Route::get('accounts/fixed/create', [AdminAccountFixingController::class, 'create'])->name('accounts.fixed.create');
    Route::post('accounts/fixed', [AdminAccountFixingController::class, 'store'])->name('accounts.fixed.store');
    Route::put('accounts/fixed/{account}', [AdminAccountFixingController::class, 'update'])->name('accounts.fixed.update');
    Route::post('accounts/fixed/{account}/toggle', [AdminAccountFixingController::class, 'toggle'])->name('accounts.fixed.toggle');
    Route::delete('accounts/fixed/{account}', [AdminAccountFixingController::class, 'destroy'])->name('accounts.fixed.destroy');
});

// pages
Route::get('/pages/misc-error', [MiscError::class, 'index'])->name('pages-misc-error');

Route::prefix('app/{company_slug}')->middleware(['web', 'company.routes', 'tenant', 'auth'])->group(function () {

    Route::get('/', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
    Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home-dashboard');
    Route::post('/logout', [CompanyAuthController::class, 'logout'])->name('company.logout');

    Route::resource('items', App\Http\Controllers\ItemsController::class);
    Route::resource('garage-items', App\Http\Controllers\GarageItemsController::class);
    Route::get('garage-items/{id}/vouchers', [App\Http\Controllers\GarageItemsController::class, 'vouchers'])->name('garage-items.vouchers');

    Route::any('/user/profile', [App\Http\Controllers\UserController::class, 'profile'])->name('profile');
    Route::get('/user/email-settings', [App\Http\Controllers\UserEmailSettingsController::class, 'edit'])->name('user.email-settings.edit');
    Route::post('/user/email-settings', [App\Http\Controllers\UserEmailSettingsController::class, 'update'])->name('user.email-settings.update');
    Route::any('/user/services/{id}', [App\Http\Controllers\UserController::class, 'services'])->name('user_services');

    Route::get('bikes/import', [\App\Http\Controllers\BikesController::class, 'importbikes'])->name('bikes.import');
    Route::post('bikes/import', [\App\Http\Controllers\BikesController::class, 'processImport'])->name('bikes.processImport');
    Route::get('bikes/export', [\App\Http\Controllers\BikesController::class, 'exportCustomizableBikes'])->name('bikes.export');
    Route::get('bikes/download-template', [\App\Http\Controllers\BikesController::class, 'downloadSampleTemplate'])->name('bikes.download-template');

    Route::any('bikes/assign_rider/{id?}', [BikesController::class, 'assign_rider'])->name('bikes.assign_rider');
    Route::any('bikes/assignrider/{id?}', [BikesController::class, 'assignrider'])->name('bikes.assignrider');
    Route::get('bikes/contracts/{id?}', [\App\Http\Controllers\BikesController::class, 'assignContract'])->name('bikes.assignContract');
    Route::get('bikes/contract/{id?}', [\App\Http\Controllers\BikesController::class, 'returnContract'])->name('bikes.returnContract');
    Route::any('bikes/contract_upload/{id?}', [\App\Http\Controllers\BikesController::class, 'contract_upload'])->name('bike_contract_upload');
    Route::get('bikes/delete/{id}', [\App\Http\Controllers\BikesController::class, 'destroy'])->name('bikes.delete');
    Route::get('bike/files/{id}', [\App\Http\Controllers\BikesController::class, 'files'])->name('bikes.files');
    Route::get('bike/maintenance/{id}', [\App\Http\Controllers\BikesController::class, 'maintenance'])->name('bikes.maintenance');

    Route::resource('bikes', App\Http\Controllers\BikesController::class);

    Route::resource('bikeMaintenance', \App\Http\Controllers\BikeMaintenanceController::class);
    Route::any('bike-maintenance/{bike}/edit', [\App\Http\Controllers\BikeMaintenanceController::class, 'edit'])->name('bike-maintenance.editForm');
    Route::any('bike-maintenance/{bike}/update', [\App\Http\Controllers\BikeMaintenanceController::class, 'update'])->name('bike-maintenance.update');
    Route::get('bike-maintenance/{maintenance}/invoice', [\App\Http\Controllers\BikeMaintenanceController::class, 'Invoice'])->name('bike-maintenance.invoice');
    Route::get('bike-maintenance/{maintenance}/sticker', [\App\Http\Controllers\BikeMaintenanceController::class, 'sticker'])->name('bike-maintenance.sticker');

    Route::get('bikes/import-bikes', [\App\Http\Controllers\BikesController::class, 'importbikes'])->name('bikes.importbikes');
    Route::post('bikes/process-import', [\App\Http\Controllers\BikesController::class, 'processImport'])->name('bikes.processImport');


    Route::resource('customers', App\Http\Controllers\CustomersController::class)->parameters(['customers' => 'id']);
    Route::get('customer/ledger/{id}', [\App\Http\Controllers\CustomersController::class, 'ledger'])->name('customer.ledger');
    Route::get('customer/files/{id}', [\App\Http\Controllers\CustomersController::class, 'files'])->name('customer.files');
    Route::get('customer/invoices/{id}', [\App\Http\Controllers\CustomersController::class, 'invoices'])->name('customer.invoices');
    Route::get('customer/receipts', [\App\Http\Controllers\CustomersController::class, 'cReceipts'])->name('customer.receipts');
    Route::get('customers/payments/{id}', [\App\Http\Controllers\CustomersController::class, 'payments'])->name('customers.payments');
    Route::get('customers/receipts/{id}', [\App\Http\Controllers\CustomersController::class, 'receipts'])->name('customers.receipts');
    // Customers Trash Routes
    Route::get('customers/trash', [\App\Http\Controllers\CustomersController::class, 'trash'])->name('customers.trash');
    Route::post('customers/trash/{id}/restore', [\App\Http\Controllers\CustomersController::class, 'restoreTrash'])->name('customers.restore');
    Route::delete('customers/trash/{id}/force-destroy', [\App\Http\Controllers\CustomersController::class, 'forceDestroyTrash'])->name('customers.force-destroy');

    Route::get('rtaFines/import', [\App\Http\Controllers\RtaFinesController::class, 'importForm'])->name('rtaFines.import.form');
    Route::post('rtaFines/import', [\App\Http\Controllers\RtaFinesController::class, 'import'])->name('rtaFines.import');

    Route::resource('rtaFines', App\Http\Controllers\RtaFinesController::class)->except(['show']);
    Route::post('rtaFines/store', [\App\Http\Controllers\RtaFinesController::class, 'store'])->name('rtaFines.store');
    Route::get('rtaFines/edit/{id}', [\App\Http\Controllers\RtaFinesController::class, 'edit'])->name('rtaFines.edit');
    Route::post('rtaFines/update', [\App\Http\Controllers\RtaFinesController::class, 'update'])->name('rtaFines.update');
    Route::get('rtaFines/create', [\App\Http\Controllers\RtaFinesController::class, 'create'])->name('rtaFines.create');
    Route::any('rtaFines/attach_file/{id}', [\App\Http\Controllers\RtaFinesController::class, 'fileUpload'])->name('rtaFines.fileupload');
    Route::get('rtaFines/delete/{id}', [\App\Http\Controllers\RtaFinesController::class, 'destroy'])->name('rtaFines.delete');

    Route::post('rtaFines/accountcreate', [\App\Http\Controllers\RtaFinesController::class, 'accountcreate'])->name('rtaFines.accountcreate');
    Route::post('rtaFines/editaccount', [\App\Http\Controllers\RtaFinesController::class, 'editaccount'])->name('rtaFines.editaccount');
    Route::get('rtaFines/deleteaccount/{id}', [\App\Http\Controllers\RtaFinesController::class, 'deleteaccount'])->name('rtaFines.deleteaccount');
    Route::get('rtaFines/tickets', [\App\Http\Controllers\RtaFinesController::class, 'tickets'])->name('rtaFines.tickets');
    Route::get('rtaFines/paid', [\App\Http\Controllers\RtaFinesController::class, 'paid'])->name('rtaFines.paid');
    Route::post('rtaFines/payfine', [\App\Http\Controllers\RtaFinesController::class, 'payfine'])->name('rtaFines.payfine');
    Route::get('rtaFines/viewvoucher/{id}', [\App\Http\Controllers\RtaFinesController::class, 'viewvoucher'])->name('rtaFines.viewvoucher');
    Route::get('rtaFines/getrider/{id}', [\App\Http\Controllers\RtaFinesController::class, 'getrider'])->name('rtaFines.getrider');


    Route::get('/customer_invoices/{id}/edit', [App\Http\Controllers\CustomerInvoicesController::class, 'edit'])->name('customer_invoice.edit');
    Route::get('/customer_invoices/{id}/clone', [App\Http\Controllers\CustomerInvoicesController::class, 'clone'])->name('customer_invoice.clone');
    Route::resource('customer_invoices', App\Http\Controllers\CustomerInvoicesController::class);

    Route::resource('employees', App\Http\Controllers\EmployeeController::class);
    Route::get('/employees/{id}/ledger', [App\Http\Controllers\EmployeeController::class, 'ledger'])->name('employee.ledger');
    Route::post('/employees/update-status', [App\Http\Controllers\EmployeeController::class, 'updateStatus'])->name('employee.update-status');
    Route::post('/employees/{id}/update-section', [App\Http\Controllers\EmployeeController::class, 'updateSection'])->name('employees.updateSection');

    Route::get('attendance/summary', [\App\Http\Controllers\AttendanceController::class, 'summary'])->name('attendance.summary');
    Route::get('attendance/export', [\App\Http\Controllers\AttendanceController::class, 'export'])->name('attendance.export');
    Route::get('attendance/summary/export', [\App\Http\Controllers\AttendanceController::class, 'exportSummary'])->name('attendance.summary.export');
    Route::resource('attendance', App\Http\Controllers\AttendanceController::class);
    Route::post('attendance/bulk-mark', [\App\Http\Controllers\AttendanceController::class, 'bulkMark'])->name('attendance.bulk-mark');
    Route::get('attendance/users/{refType}', [\App\Http\Controllers\AttendanceController::class, 'getUsers'])->name('attendance.users');

    Route::resource('VisaExpense', App\Http\Controllers\VisaexpenseController::class);

    // Visa Status Management Routes
    Route::resource('visa-statuses', App\Http\Controllers\VisaStatusController::class);
    Route::post('visa-statuses/reorder', [App\Http\Controllers\VisaStatusController::class, 'reorder'])->name('visa-statuses.reorder');
    Route::get('visa-statuses/{id}/toggle-active', [App\Http\Controllers\VisaStatusController::class, 'toggleActive'])->name('visa-statuses.toggle-active');
    Route::post('VisaExpense/store', [\App\Http\Controllers\VisaexpenseController::class, 'store'])->name('VisaExpense.store');
    Route::get('VisaExpense/edit/{id}', [\App\Http\Controllers\VisaexpenseController::class, 'edit'])->name('VisaExpense.edit');
    Route::post('VisaExpense/update', [\App\Http\Controllers\VisaexpenseController::class, 'update'])->name('VisaExpense.update');
    Route::get('VisaExpense/create/{id}', [\App\Http\Controllers\VisaexpenseController::class, 'create'])->name('VisaExpense.create');
    Route::any('VisaExpense/attach_file/{id}', [\App\Http\Controllers\VisaexpenseController::class, 'fileUpload'])->name('VisaExpense.fileupload');
    Route::get('VisaExpense/delete/{id}', [\App\Http\Controllers\VisaexpenseController::class, 'destroy'])->name('VisaExpense.delete');
    Route::get('VisaExpense/installmentPlan/{id}', [\App\Http\Controllers\VisaexpenseController::class, 'installmentPlan'])->name('VisaExpense.installmentPlan');

    // Settings panel: registered under /app/{company_slug}/ — see routes/settings_panel.php




    // Simple Installment Plan Routes
    Route::get('VisaExpense/createInstallmentPlanForm/{riderId}', [\App\Http\Controllers\VisaexpenseController::class, 'createInstallmentPlanForm'])->name('VisaExpense.createInstallmentPlanForm');
    Route::post('VisaExpense/createInstallmentPlan', [\App\Http\Controllers\VisaexpenseController::class, 'createInstallmentPlan'])->name('VisaExpense.createInstallmentPlan');
    Route::post('VisaExpense/payInstallment', [\App\Http\Controllers\VisaexpenseController::class, 'payInstallment'])->name('VisaExpense.payInstallment');
    Route::post('VisaExpense/updateInstallmentField', [\App\Http\Controllers\VisaexpenseController::class, 'updateInstallmentField'])->name('VisaExpense.updateInstallmentField');
    Route::post('VisaExpense/finalizePayment', [\App\Http\Controllers\VisaexpenseController::class, 'finalizePayment'])->name('VisaExpense.finalizePayment');
    Route::get('VisaExpense/deleteInstallment/{id}', [\App\Http\Controllers\VisaexpenseController::class, 'deleteInstallment'])->name('VisaExpense.deleteInstallment');
    Route::post('VisaExpense/getVisaStatusFee', [\App\Http\Controllers\VisaexpenseController::class, 'getVisaStatusFee'])->name('VisaExpense.getVisaStatusFee');
    Route::get('VisaExpense/generateInstallmentInvoice/{riderId}', [\App\Http\Controllers\VisaexpenseController::class, 'generateInstallmentInvoice'])->name('VisaExpense.generateInstallmentInvoice');
    Route::get('VisaExpense/autoMarkInstallments/{riderId?}', [\App\Http\Controllers\VisaexpenseController::class, 'autoMarkInstallmentsAsPaid'])->name('VisaExpense.autoMarkInstallments');
    Route::post('VisaExpense/recalculateInstallments', [\App\Http\Controllers\VisaexpenseController::class, 'recalculateInstallments'])->name('VisaExpense.recalculateInstallments');

    Route::post('accountcreate', [\App\Http\Controllers\VisaexpenseController::class, 'accountcreate'])->name('VisaExpense.accountcreate');
    Route::post('editaccount', [\App\Http\Controllers\VisaexpenseController::class, 'editaccount'])->name('VisaExpense.editaccount');
    Route::get('VisaExpense/deleteaccount/{id}', [\App\Http\Controllers\VisaexpenseController::class, 'deleteaccount'])->name('VisaExpense.deleteaccount');
    Route::get('VisaExpense/generatentries/{id}', [\App\Http\Controllers\VisaexpenseController::class, 'generatentries'])->name('VisaExpense.generatentries');
    Route::post('VisaExpense/payfine', [\App\Http\Controllers\VisaexpenseController::class, 'payfine'])->name('VisaExpense.payfine');
    Route::get('VisaExpense/viewvoucher/{id}', [\App\Http\Controllers\VisaexpenseController::class, 'viewvoucher'])->name('VisaExpense.viewvoucher');
    Route::get('VisaExpense/getrider/{id}', [\App\Http\Controllers\VisaexpenseController::class, 'getrider']);


    Route::get('sims/trash', [\App\Http\Controllers\SimsController::class, 'trash'])->name('sims.trash');
    Route::get('sims/trash/{id}', [\App\Http\Controllers\SimsController::class, 'showTrash'])->name('sims.showTrash');
    Route::post('sims/empty-trash', [\App\Http\Controllers\SimsController::class, 'emptyTrash'])->name('sims.emptyTrash');
    Route::get('sims/restore/{id}', [\App\Http\Controllers\SimsController::class, 'restore'])->name('sims.restore');
    Route::match(['get', 'post'], 'sims/assign/{id}', [\App\Http\Controllers\SimsController::class, 'assign'])->name('sims.assign');
    Route::match(['get', 'post'], 'sims/return/{id}', [\App\Http\Controllers\SimsController::class, 'return'])->name('sims.return');
    Route::get('sims/export', [\App\Http\Controllers\SimsController::class, 'export'])->name('sims.export');
    Route::match(['get', 'post'], 'sims/import', [\App\Http\Controllers\SimsController::class, 'import'])->name('sims.import');


    Route::resource('sims', App\Http\Controllers\SimsController::class);
    Route::get('sims/delete/{id}', [\App\Http\Controllers\SimsController::class, 'destroy'])->name('sims.delete');

    Route::get('simCompanies/trash', [\App\Http\Controllers\SimCompaniesController::class, 'trash'])->name('simCompanies.trash');
    Route::post('simCompanies/trash/{id}/restore', [\App\Http\Controllers\SimCompaniesController::class, 'restoreTrash'])->name('simCompanies.restore');
    Route::delete('simCompanies/trash/{id}/force-destroy', [\App\Http\Controllers\SimCompaniesController::class, 'forceDestroyTrash'])->name('simCompanies.force-destroy');
    Route::resource('simCompanies', App\Http\Controllers\SimCompaniesController::class);
    Route::delete('simCompanies/delete/{id}', [\App\Http\Controllers\SimCompaniesController::class, 'destroy'])->name('simCompanies.delete');
    Route::get('simInvoices', [\App\Http\Controllers\SimInvoicesController::class, 'index'])->name('simInvoices.index');
    Route::get('simInvoices/create/{vendorId?}', [\App\Http\Controllers\SimInvoicesController::class, 'create'])->name('simInvoices.create');
    Route::get('simInvoices/create-from-clone/{id}', [\App\Http\Controllers\SimInvoicesController::class, 'createFromClone'])->name('simInvoices.createFromClone');
    Route::post('simInvoices/store', [\App\Http\Controllers\SimInvoicesController::class, 'store'])->name('simInvoices.store');
    Route::get('simInvoices/{id}', [\App\Http\Controllers\SimInvoicesController::class, 'show'])->name('simInvoices.show');
    Route::get('simInvoices/{id}/edit', [\App\Http\Controllers\SimInvoicesController::class, 'edit'])->name('simInvoices.edit');
    Route::put('simInvoices/{id}', [\App\Http\Controllers\SimInvoicesController::class, 'update'])->name('simInvoices.update');
    Route::delete('simInvoices/{id}', [\App\Http\Controllers\SimInvoicesController::class, 'destroy'])->name('simInvoices.destroy');
    Route::post('simInvoices/{id}/clone', [\App\Http\Controllers\SimInvoicesController::class, 'clone'])->name('simInvoices.clone');
    Route::get('simInvoices/vendor/{id}/sims', [\App\Http\Controllers\SimInvoicesController::class, 'getSims'])->name('simInvoices.getSims');
    Route::get('simInvoices/{id}/payment-voucher/create', [\App\Http\Controllers\SimInvoicesController::class, 'createPaymentVoucher'])->name('simInvoices.paymentVoucher.create');
    Route::post('simInvoices/{id}/payment-voucher', [\App\Http\Controllers\SimInvoicesController::class, 'storePaymentVoucher'])->name('simInvoices.paymentVoucher.store');

    Route::get('bikeRentCompanies/trash', [\App\Http\Controllers\BikeRentCompaniesController::class, 'trash'])->name('bikeRentCompanies.trash');
    Route::post('bikeRentCompanies/trash/{id}/restore', [\App\Http\Controllers\BikeRentCompaniesController::class, 'restoreTrash'])->name('bikeRentCompanies.restore');
    Route::delete('bikeRentCompanies/trash/{id}/force-destroy', [\App\Http\Controllers\BikeRentCompaniesController::class, 'forceDestroyTrash'])->name('bikeRentCompanies.force-destroy');
    Route::resource('bikeRentCompanies', App\Http\Controllers\BikeRentCompaniesController::class);
    Route::delete('bikeRentCompanies/delete/{id}', [\App\Http\Controllers\BikeRentCompaniesController::class, 'destroy'])->name('bikeRentCompanies.delete');

    /* Rider section starts from here */

    Route::resource('riders', App\Http\Controllers\RidersController::class);
    Route::post('riders/filter-ajax', [\App\Http\Controllers\RidersController::class, 'filterAjax'])->name('riders.filterAjax');
    Route::get('riders/dropdown-options/modal', [\App\Http\Controllers\RidersController::class, 'dropdownOptionModal'])->name('riders.dropdown-options.modal');
    Route::post('riders/dropdown-options', [\App\Http\Controllers\RidersController::class, 'storeDropdownOption'])->name('riders.dropdown-options.store');
    Route::any('riders/job_status/{id?}', [\App\Http\Controllers\RidersController::class, 'job_status'])->name('rider.job_status');


    Route::get('riders/timeline/{id?}', [\App\Http\Controllers\RidersController::class, 'timeline'])->name('rider.timeline');
    Route::get('riders/contract/{id?}', [\App\Http\Controllers\RidersController::class, 'contract'])->name('rider.contract');
    Route::any('riders/contract_upload/{id?}', [\App\Http\Controllers\RidersController::class, 'contract_upload'])->name('rider_contract_upload');
    Route::any('riders/picture_upload/{id?}', [\App\Http\Controllers\RidersController::class, 'picture_upload'])->name('rider_picture_upload');
    Route::any('riders/rider-document/{id}', [\App\Http\Controllers\RidersController::class, 'document'])->name('rider.document');
    Route::get('rider/updateRider', [\App\Http\Controllers\RidersController::class, 'updateRider'])->name('rider.updateRider');
    Route::get('rider/delete/{id}', [\App\Http\Controllers\RidersController::class, 'destroy'])->name('rider.delete');
    Route::get('riders/ledger/{id}', [\App\Http\Controllers\RidersController::class, 'ledger'])->name('rider.ledger');
    Route::get('riders/attendance/{id}', [\App\Http\Controllers\RidersController::class, 'attendance'])->name('rider.attendance');
    Route::get('riders/activities/{id}', [\App\Http\Controllers\RidersController::class, 'activities'])->name('rider.activities');
    Route::get('riders/activities/{id}/pdf', [\App\Http\Controllers\RidersController::class, 'activitiesPdf'])->name('riders.activities.pdf');
    Route::get('riders/activities/{id}/print', [\App\Http\Controllers\RidersController::class, 'activitiesPrint'])->name('riders.activities.print');
    Route::get('riders/invoices/{id}', [\App\Http\Controllers\RidersController::class, 'invoices'])->name('rider.invoices');
    Route::any('riders/sendemail/{id}', [\App\Http\Controllers\RidersController::class, 'sendEmail'])->name('rider.sendemail');
    Route::get('riders/emails/{id}', [\App\Http\Controllers\RidersController::class, 'emails'])->name('rider.emails');
    Route::get('rider/exportRiders', [\App\Http\Controllers\RidersController::class, 'exportRiders'])->name('rider.exportRiders');
    Route::get('rider/exportCustomizableRiders', [\App\Http\Controllers\RidersController::class, 'exportCustomizableRiders'])->name('rider.exportCustomizableRiders');

    // User Table Settings Routes
    Route::prefix('user-table-settings')->group(function () {
        Route::get('/', [\App\Http\Controllers\UserTableSettingsController::class, 'getSettings'])->name('user-table-settings.get');
        Route::post('/', [\App\Http\Controllers\UserTableSettingsController::class, 'saveSettings'])->name('user-table-settings.save');
        Route::delete('/', [\App\Http\Controllers\UserTableSettingsController::class, 'resetSettings'])->name('user-table-settings.reset');
        Route::get('/all', [\App\Http\Controllers\UserTableSettingsController::class, 'getAllSettings'])->name('user-table-settings.all');
    });
    Route::get('riders/files/{id}', [\App\Http\Controllers\RidersController::class, 'files'])->name('rider.files');
    Route::get('riders/items/{id}', [\App\Http\Controllers\RidersController::class, 'items'])->name('rider.items');
    Route::get('riders/additems/{id}', [\App\Http\Controllers\RidersController::class, 'additems'])->name('riders.additems');
    Route::post('riders/storeitems/{id}', [\App\Http\Controllers\RidersController::class, 'storeitems'])->name('riders.storeitems');
    Route::post('riders/{rider_id}/additem', [\App\Http\Controllers\RidersController::class, 'additem'])->name('riders.additem');
    Route::get('riders/{rider_id}/edititem/{item_id}', [\App\Http\Controllers\RidersController::class, 'edititem'])->name('riders.edititem');
    Route::post('riders/{rider_id}/updateitem/{item_id}', [\App\Http\Controllers\RidersController::class, 'updateitem'])->name('riders.updateitem');
    Route::delete('riders/{rider_id}/deleteitem/{item_id}', [\App\Http\Controllers\RidersController::class, 'deleteitem'])->name('riders.deleteitem');
    Route::get('riders/createitems/{id}', [\App\Http\Controllers\RidersController::class, 'createitems'])->name('riders.createitems');
    Route::get('riders/visaloan/{id}', [\App\Http\Controllers\RidersController::class, 'visaloan'])->name('riders.visaloan');
    Route::get('riders/advanceloan/{id}', [\App\Http\Controllers\RidersController::class, 'advanceloan'])->name('riders.advanceloan');
    Route::get('riders/cod/{id}', [\App\Http\Controllers\RidersController::class, 'cod'])->name('riders.cod');
    Route::get('riders/penalty/{id}', [\App\Http\Controllers\RidersController::class, 'penalty'])->name('riders.penalty');
    Route::get('riders/incentive/{id}', [\App\Http\Controllers\RidersController::class, 'incentive'])->name('riders.incentive');
    Route::get('riders/payment/{id}', [\App\Http\Controllers\RidersController::class, 'payment'])->name('riders.payment');
    // Unified voucher modal (Advance Loan, COD, Penalty, Payment, Vendor Charges)
    Route::get('riders/voucher/{id}', [\App\Http\Controllers\RidersController::class, 'voucher'])->name('riders.voucher');
    Route::post('riders/storevisaloan', [\App\Http\Controllers\RidersController::class, 'storevisaloan'])->name('riders.storevisaloan');
    Route::post('riders/storecod', [\App\Http\Controllers\RidersController::class, 'storecod'])->name('riders.storecod');
    Route::post('riders/storepenalty', [\App\Http\Controllers\RidersController::class, 'storepenalty'])->name('riders.storepenalty');
    Route::post('riders/storeincentive', [\App\Http\Controllers\RidersController::class, 'storeincentive'])->name('riders.storeincentive');
    Route::post('riders/storepayment', [\App\Http\Controllers\RidersController::class, 'storepayment'])->name('riders.storepayment');
    // Riders vouchers import (modal - existing)
    Route::any('rider/voucher-import', [\App\Http\Controllers\RidersController::class, 'importVouchers'])->name('riders.voucher_import');
    // Standalone Import Rider Vouchers page
    Route::match(['get', 'post'], 'rider/import-rider-vouchers', [\App\Http\Controllers\RidersController::class, 'importRiderVouchers'])
        ->name('riders.import_rider_vouchers');
    Route::post('riders/storeadvanceloan', [\App\Http\Controllers\RidersController::class, 'storeadvanceloan'])->name('riders.storeadvanceloan');
    Route::post('riders/update-section/{id}', [\App\Http\Controllers\RidersController::class, 'updateSection'])->name('riders.updateSection');
    Route::post('riders/set-rider-top-option/{id}', [\App\Http\Controllers\RidersController::class, 'setRiderTopOption'])->name('riders.setRiderTopOption');
    Route::post('riders/return-bike/{id}', [\App\Http\Controllers\RidersController::class, 'returnBike'])->name('riders.returnBike');
    Route::post('riders/add-recruiter', [\App\Http\Controllers\RidersController::class, 'addRecruiter'])->name('riders.addRecruiter');
    Route::get('riders/vendorcharges/{id}', [\App\Http\Controllers\RidersController::class, 'vendorcharges'])->name('riders.vendorcharges');
    Route::post('riders/storevendorcharges', [\App\Http\Controllers\RidersController::class, 'storevendorcharges'])->name('riders.storevendorcharges');



    Route::resource('riderleads', App\Http\Controllers\riderhiringController::class);




    Route::get('payments/{id}/clone', [\App\Http\Controllers\PaymentController::class, 'clone'])->name('payments.clone');
    Route::resource('payments', App\Http\Controllers\PaymentController::class);
    Route::get('receipts/{id}/clone', [\App\Http\Controllers\ReceiptController::class, 'clone'])->name('receipts.clone');
    Route::resource('receipts', App\Http\Controllers\ReceiptController::class);










    Route::get('riders/file-manager', function () {
        return view('riders.file-manager');
    })->name('rider.file-manager');

    Route::resource('riderEmails', App\Http\Controllers\RiderEmailsController::class);


    Route::resource('riderInvoices', App\Http\Controllers\RiderInvoicesController::class);
    Route::any('rider/invoice-import', [\App\Http\Controllers\RiderInvoicesController::class, 'import'])->name('rider.invoice_import');
    Route::any('rider/invoice-import-paid', [\App\Http\Controllers\RiderInvoicesController::class, 'importPaid'])->name('riderInvoices.importPaid');
    Route::any('rider/invoice-mark-paid/{id}', [\App\Http\Controllers\RiderInvoicesController::class, 'markAsPaid'])->name('riderInvoices.markAsPaid');
    Route::get('search_item_price/{RID}/{itemID}', [\App\Http\Controllers\ItemsController::class, 'search_item_price']);
    Route::get('riderInvoices/delete/{id}', [\App\Http\Controllers\RiderInvoicesController::class, 'destroy'])->name('riderInvoices.delete');
    Route::post('riderInvoices/bulk-delete', [\App\Http\Controllers\RiderInvoicesController::class, 'bulkDelete'])->name('riderInvoices.bulkDelete');
    Route::resource('employeeInvoices', App\Http\Controllers\EmployeeInvoicesController::class);
    Route::get('employeeInvoices/delete/{id}', [\App\Http\Controllers\EmployeeInvoicesController::class, 'destroy'])->name('employeeInvoices.delete');
    Route::post('employeeInvoices/bulk-delete', [\App\Http\Controllers\EmployeeInvoicesController::class, 'bulkDelete'])->name('employeeInvoices.bulkDelete');

    Route::resource('riderAttendances', App\Http\Controllers\RiderAttendanceController::class);
    Route::any('rider/attendance-import', [\App\Http\Controllers\RiderAttendanceController::class, 'import'])->name('rider.attendance_import');

    Route::resource('riderActivities', App\Http\Controllers\RiderActivitiesController::class);
    Route::any('rider/activities-import', [\App\Http\Controllers\RiderActivitiesController::class, 'import'])->name('rider.activities_import');
    Route::any('rider/keeta-activities-import', [\App\Http\Controllers\RiderActivitiesController::class, 'importKeeta'])->name('rider.keeta_activities_import');
    Route::get('rider/activities-import/errors', [\App\Http\Controllers\RiderActivitiesController::class, 'importErrors'])->name('rider.activities_import_errors');




    Route::get('rider/riderliveActivities', [\App\Http\Controllers\RiderActivitiesController::class, 'liveactivities'])->name('rider.liveactivities');
    Route::any('rider/live-activities-import', [\App\Http\Controllers\RiderActivitiesController::class, 'liveimportactivities'])->name('rider.live_activities_import');
    Route::get('rider/live-activities-import/errors', [\App\Http\Controllers\RiderActivitiesController::class, 'liveimportErrors'])->name('rider.live_activities_import_errors');
    /* Rider section end here */


    Route::resource('riderActivities', App\Http\Controllers\RiderActivitiesController::class);

    Route::resource('supplier_invoices', SupplierInvoicesController::class);
    Route::get('supplierInvoices/delete/{id}', [\App\Http\Controllers\SupplierInvoicesController::class, 'destroy'])->name('supplierInvoices.delete');

    Route::get('/item/{id}/price', [ItemsController::class, 'getPrice'])->name('item.price');

    Route::get('/get-item-price/{id}', [ItemsController::class, 'getItemPrice'])->name('item.getPrice');
    Route::get('items/delete/{id}', [ItemsController::class, 'destroy'])->name('items.delete');
    Route::get('/get-owners', [ItemsController::class, 'getOwners'])->name('get-owners');

    Route::resource('files', FilesController::class);

    Route::resource('vendors', App\Http\Controllers\VendorsController::class);

    Route::get('vendors/delete/{id}', [\App\Http\Controllers\VendorsController::class, 'destroy'])->name('vendors.delete');
    // Vendors Trash Routes
    Route::get('vendors/trash', [\App\Http\Controllers\VendorsController::class, 'trash'])->name('vendors.trash');
    Route::post('vendors/trash/{id}/restore', [\App\Http\Controllers\VendorsController::class, 'restoreTrash'])->name('vendors.restore');
    Route::delete('vendors/trash/{id}/force-destroy', [\App\Http\Controllers\VendorsController::class, 'forceDestroyTrash'])->name('vendors.force-destroy');

    Route::resource('recruiters', App\Http\Controllers\RecruitersController::class);
    Route::get('recruiters/{recruiter}/riders', [RecruitersController::class, 'showRiders'])->name('recruiters.riders');
    Route::delete('recruiters/delete/{id}', [\App\Http\Controllers\RecruitersController::class, 'destroy'])->name('recruiters.delete');
    Route::get('recruiters', [\App\Http\Controllers\RecruitersController::class, 'index'])->name('recruiters.index');
    // Recruiters Trash Routes
    Route::get('recruiters/trash', [\App\Http\Controllers\RecruitersController::class, 'trash'])->name('recruiters.trash');
    Route::post('recruiters/trash/{id}/restore', [\App\Http\Controllers\RecruitersController::class, 'restoreTrash'])->name('recruiters.restore');
    Route::delete('recruiters/trash/{id}/force-destroy', [\App\Http\Controllers\RecruitersController::class, 'forceDestroyTrash'])->name('recruiters.force-destroy');
    Route::post('recruiters/{recruiter}/assign-riders', [\App\Http\Controllers\RecruitersController::class, 'assignRiders'])->name('recruiters.assign-riders');
    Route::get('recruiters/unassigned-riders', [\App\Http\Controllers\RecruitersController::class, 'getUnassignedRiders'])->name('recruiters.unassigned-riders');
    Route::get('recruiters/{recruiter}/assign-riders', [\App\Http\Controllers\RecruitersController::class, 'showAssignRidersView'])->name('recruiters.assign-riders');
    Route::post('recruiters/{recruiter}/remove-riders', [\App\Http\Controllers\RecruitersController::class, 'removeRiders'])->name('recruiters.remove-riders');

    Route::resource('bikeHistories', App\Http\Controllers\BikeHistoryController::class);

    Route::resource('simHistories', App\Http\Controllers\SimHistoryController::class);
    Route::any('fuel_transactions/import', [\App\Http\Controllers\FuelDataController::class, 'import'])->name('fuel_data.import');
    Route::get('fuel_transactions/importSample', [\App\Http\Controllers\FuelDataController::class, 'downloadTemplate'])->name('fuel_data.importSample');
    Route::get('fuel_data/summary', [\App\Http\Controllers\FuelDataController::class, 'monthlySummary'])->name('fuel_data.summary');
    Route::get('fuel_invoice/{rider_id}/{billing_month}', [\App\Http\Controllers\FuelDataController::class, 'show2'])->name('fuel_data.rider_monthly_summary');
    Route::resource('fuel_data', App\Http\Controllers\FuelDataController::class);

    Route::get('fuelCompanies/trash', [\App\Http\Controllers\FuelCompaniesController::class, 'trash'])->name('fuelCompanies.trash');
    Route::post('fuelCompanies/trash/{id}/restore', [\App\Http\Controllers\FuelCompaniesController::class, 'restoreTrash'])->name('fuelCompanies.restore');
    Route::delete('fuelCompanies/trash/{id}/force-destroy', [\App\Http\Controllers\FuelCompaniesController::class, 'forceDestroyTrash'])->name('fuelCompanies.force-destroy');
    Route::resource('fuelCompanies', App\Http\Controllers\FuelCompaniesController::class);
    Route::delete('fuelCompanies/delete/{id}', [\App\Http\Controllers\FuelCompaniesController::class, 'destroy'])->name('fuelCompanies.delete');

    Route::resource('fuelCards', App\Http\Controllers\FuelCardController::class);
    Route::any('fuelcards/import', [\App\Http\Controllers\FuelCardController::class, 'import'])->name('fuelCards.import');
    Route::get('fuelcards/export', [\App\Http\Controllers\FuelCardController::class, 'export'])->name('fuelCards.export');

    Route::any('fuelcards/assign/{id}', [\App\Http\Controllers\FuelCardHistoryController::class, 'assign'])->name('fuelCards.assign');
    Route::any('fuelcards/return/{id}', [\App\Http\Controllers\FuelCardHistoryController::class, 'return'])->name('fuelCards.return');
    Route::any('fuelcards/update_assignment/{id}', [\App\Http\Controllers\FuelCardHistoryController::class, 'updateAssignment'])->name('fuelCards.update_assignment');

    Route::resource('leasingCompanies', App\Http\Controllers\LeasingCompaniesController::class);
    Route::delete('leasingCompanies/delete/{id}', [\App\Http\Controllers\LeasingCompaniesController::class, 'destroy'])->name('leasingCompanies.delete');
    // Leasing Companies Trash Routes
    Route::get('leasingCompanies/trash', [\App\Http\Controllers\LeasingCompaniesController::class, 'trash'])->name('leasingCompanies.trash');
    Route::post('leasingCompanies/trash/{id}/restore', [\App\Http\Controllers\LeasingCompaniesController::class, 'restoreTrash'])->name('leasingCompanies.restore');
    Route::delete('leasingCompanies/trash/{id}/force-destroy', [\App\Http\Controllers\LeasingCompaniesController::class, 'forceDestroyTrash'])->name('leasingCompanies.force-destroy');

    // Leasing Company Invoice Routes
    Route::get('leasingCompanyInvoices', [\App\Http\Controllers\LeasingCompaniesController::class, 'indexInvoices'])->name('leasingCompanyInvoices.index');
    Route::get('leasingCompanyInvoices/create/{leasingCompanyId?}', [\App\Http\Controllers\LeasingCompaniesController::class, 'createInvoice'])->name('leasingCompanyInvoices.create');
    Route::get('leasingCompanyInvoices/create-from-clone/{id}', [\App\Http\Controllers\LeasingCompaniesController::class, 'createFromClone'])->name('leasingCompanyInvoices.createFromClone');
    Route::post('leasingCompanyInvoices/store', [\App\Http\Controllers\LeasingCompaniesController::class, 'storeInvoice'])->name('leasingCompanyInvoices.store');
    Route::get('leasingCompanyInvoices/{id}', [\App\Http\Controllers\LeasingCompaniesController::class, 'showInvoice'])->name('leasingCompanyInvoices.show');
    Route::get('leasingCompanyInvoices/{id}/edit', [\App\Http\Controllers\LeasingCompaniesController::class, 'editInvoice'])->name('leasingCompanyInvoices.edit');
    Route::put('leasingCompanyInvoices/{id}', [\App\Http\Controllers\LeasingCompaniesController::class, 'updateInvoice'])->name('leasingCompanyInvoices.update');
    Route::delete('leasingCompanyInvoices/{id}', [\App\Http\Controllers\LeasingCompaniesController::class, 'destroyInvoice'])->name('leasingCompanyInvoices.destroy');
    Route::post('leasingCompanyInvoices/{id}/clone', [\App\Http\Controllers\LeasingCompaniesController::class, 'cloneInvoice'])->name('leasingCompanyInvoices.clone');

    // Leasing Company Billing Invoice Routes (separate module)
    Route::get('leasingCompanyBillingInvoices', [\App\Http\Controllers\LeasingCompanyBillingInvoicesController::class, 'index'])->name('leasingCompanyBillingInvoices.index');
    Route::get('leasingCompanyBillingInvoices/create/{customerId?}', [\App\Http\Controllers\LeasingCompanyBillingInvoicesController::class, 'create'])->name('leasingCompanyBillingInvoices.create');
    Route::get('leasingCompanyBillingInvoices/create-from-clone/{id}', [\App\Http\Controllers\LeasingCompanyBillingInvoicesController::class, 'createFromClone'])->name('leasingCompanyBillingInvoices.createFromClone');
    Route::post('leasingCompanyBillingInvoices/store', [\App\Http\Controllers\LeasingCompanyBillingInvoicesController::class, 'store'])->name('leasingCompanyBillingInvoices.store');
    Route::get('leasingCompanyBillingInvoices/{id}', [\App\Http\Controllers\LeasingCompanyBillingInvoicesController::class, 'show'])->name('leasingCompanyBillingInvoices.show');
    Route::get('leasingCompanyBillingInvoices/{id}/edit', [\App\Http\Controllers\LeasingCompanyBillingInvoicesController::class, 'edit'])->name('leasingCompanyBillingInvoices.edit');
    Route::put('leasingCompanyBillingInvoices/{id}', [\App\Http\Controllers\LeasingCompanyBillingInvoicesController::class, 'update'])->name('leasingCompanyBillingInvoices.update');
    Route::delete('leasingCompanyBillingInvoices/{id}', [\App\Http\Controllers\LeasingCompanyBillingInvoicesController::class, 'destroy'])->name('leasingCompanyBillingInvoices.destroy');
    Route::post('leasingCompanyBillingInvoices/{id}/clone', [\App\Http\Controllers\LeasingCompanyBillingInvoicesController::class, 'clone'])->name('leasingCompanyBillingInvoices.clone');
    Route::get('leasingCompanies/{id}/invoices', [\App\Http\Controllers\LeasingCompaniesController::class, 'createInvoice'])->name('leasingCompanies.createInvoice');
    Route::post('leasingCompanies/{id}/invoices', [\App\Http\Controllers\LeasingCompaniesController::class, 'storeInvoice'])->name('leasingCompanies.storeInvoice');
    Route::get('leasingCompanies/{id}/bikes', [\App\Http\Controllers\LeasingCompaniesController::class, 'getBikes'])->name('leasingCompanies.getBikes');
    Route::get('leasingCompanies/receipts/{id}', [\App\Http\Controllers\LeasingCompaniesController::class, 'receipts'])->name('leasingCompanies.receipts');
    Route::get('leasingCompany/receipts', [\App\Http\Controllers\LeasingCompaniesController::class, 'receipt'])->name('leasingCompanies.receipt');
    Route::get('leasingCompany/payments', [\App\Http\Controllers\LeasingCompaniesController::class, 'payment'])->name('leasingCompanies.payment');
    Route::get('leasingCompanies/payments/{id}', [\App\Http\Controllers\LeasingCompaniesController::class, 'payments'])->name('leasingCompanies.payments');
    Route::get('leasingCompany/files/{id}', [\App\Http\Controllers\LeasingCompaniesController::class, 'files'])->name('leasingCompany.files');
    Route::get('leasingCompany/ledger/{id}', [\App\Http\Controllers\LeasingCompaniesController::class, 'ledger'])->name('leasingCompany.ledger');
    Route::resource('garages', App\Http\Controllers\GaragesController::class);
    Route::get('garages/delete/{id}', [\App\Http\Controllers\GaragesController::class, 'destroy'])->name('garages.delete');

    Route::resource('banks', App\Http\Controllers\BanksController::class);
    Route::get('banks/ledger/{id}', [\App\Http\Controllers\BanksController::class, 'ledger'])->name('bank.ledger');
    Route::get('banks/files/{id}', [\App\Http\Controllers\BanksController::class, 'files'])->name('bank.files');
    Route::get('banks/delete/{id}', [\App\Http\Controllers\BanksController::class, 'destroy'])->name('bank.delete');
    Route::get('banks/receipts/{id}', [\App\Http\Controllers\BanksController::class, 'receipts'])->name('banks.receipts');
    Route::get('banks/payments/{id}', [\App\Http\Controllers\BanksController::class, 'payments'])->name('banks.payments');
    Route::get('banks/cheques/{id}', [\App\Http\Controllers\BanksController::class, 'cheques'])->name('banks.cheques');

    Route::post('/cheques/status/{id}', [App\Http\Controllers\ChequesController::class, 'updateStatus'])->name('cheques.update-status');
    Route::get('cheques/change_status/{id}', [\App\Http\Controllers\ChequesController::class, 'statusForm'])->name('cheques.status-form');
    Route::resource('cheques', App\Http\Controllers\ChequesController::class);

    // Soft Delete Routes for Banks - DEPRECATED: Use centralized trash module (/trash)
    // Route::get('banks/trashed/list', [\App\Http\Controllers\BanksController::class, 'trashed'])->name('banks.trashed');
    // Route::post('banks/{id}/restore', [\App\Http\Controllers\BanksController::class, 'restore'])->name('banks.restore');
    // Route::delete('banks/{id}/force-delete', [\App\Http\Controllers\BanksController::class, 'forceDestroy'])->name('banks.force-destroy');

    Route::get('vouchers/{id}/clone', [\App\Http\Controllers\VouchersController::class, 'cloneVoucher'])->name('vouchers.clone');
    Route::get('vouchers/list-sidebar', [\App\Http\Controllers\VouchersController::class, 'listSidebar'])->name('vouchers.list-sidebar');
    Route::resource('vouchers', \App\Http\Controllers\VouchersController::class);
    Route::any('voucher/import', [\App\Http\Controllers\VouchersController::class, 'import'])->name('voucher.import');
    Route::get('get_invoice_balance', [\App\Http\Controllers\VouchersController::class, 'GetInvoiceBalance'])->name('get_invoice_balance');
    Route::get('fetch_invoices/{id}/{vt}', [\App\Http\Controllers\VouchersController::class, 'fetch_invoices']);
    /*   Route::any('attach_file/{id}', 'VouchersController@fileUpload'); */
    Route::any('voucher/attach_file/{id}', [\App\Http\Controllers\VouchersController::class, 'fileUpload'])->name('voucher.fileupload');


    Route::prefix('settings')->group(function () {

        Route::any('/company', [HomeController::class, 'settings'])->name('settings');
        Route::get('/erp', [App\Http\Controllers\ErpSettingsController::class, 'index'])->name('settings.erp');
        Route::post('/erp', [App\Http\Controllers\ErpSettingsController::class, 'store'])->name('settings.erp.store');
        Route::resource('departments', App\Http\Controllers\DepartmentsController::class);
        Route::resource('dropdowns', App\Http\Controllers\DropdownsController::class);
    });
    Route::prefix('reports')->group(function () {
        Route::get('/rider_report', [ReportController::class, 'rider_report'])->name('reports.rider_report');
        Route::post('/rider_report_data', [ReportController::class, 'rider_report_data'])->name('reports.rider_report_data');
        Route::get('/rider_monthly_report', [ReportController::class, 'rider_monthly_report'])->name('reports.rider_monthly_report');
        Route::post('/rider_monthly_report_data', [ReportController::class, 'rider_monthly_report_data'])->name('reports.rider_monthly_report_data');
    });



    Route::get('/itmeslist', function () {
        return App\Helpers\General::dropdownitems();
    });

    Route::prefix('accounts')->group(function () {

        Route::get('detail/{id}', [App\Http\Controllers\AccountsController::class, 'accountDetail'])->name('accounts.detail');
        Route::get('detail/{id}/ledger-entries', [App\Http\Controllers\AccountsController::class, 'ledgerEntries'])->name('accounts.ledgerEntries');
        Route::resource('accounts', App\Http\Controllers\AccountsController::class)->parameters(['accounts' => 'id']);
        Route::get('tree', [\App\Http\Controllers\AccountsController::class, 'tree'])->name('accounts.tree');
        // Accounts Trash Routes
        Route::get('trash', [\App\Http\Controllers\AccountsController::class, 'trash'])->name('accounts.trash');
        Route::post('trash/{id}/restore', [\App\Http\Controllers\AccountsController::class, 'restoreTrash'])->name('accounts.restore');
        Route::delete('trash/{id}/force-destroy', [\App\Http\Controllers\AccountsController::class, 'forceDestroyTrash'])->name('accounts.force-destroy');

        Route::get('/ledgerreport', [LedgerController::class, 'ledger'])->name('accounts.ledgerreport');
        Route::get('/ledger', [LedgerController::class, 'index'])->name('accounts.ledger');
        Route::get('/ledger/data', [LedgerController::class, 'getLedgerData'])->name('ledger.data');
        Route::get('/ledger/export', [LedgerController::class, 'export'])->name('ledger.export');
        Route::get('/vat', [App\Http\Controllers\VatController::class, 'index'])->name('vat.index');
        Route::get('/vat/returns', [App\Http\Controllers\VatController::class, 'returnsIndex'])->name('vat.returns.index');
        Route::get('/vat/returns/{vat_return}', [App\Http\Controllers\VatController::class, 'returnsShow'])->name('vat.returns.show');
        Route::post('/vat/return-file', [App\Http\Controllers\VatController::class, 'fileReturn'])->name('vat.return.file');
        Route::patch('/vat/returns/{vat_return}/status', [App\Http\Controllers\VatController::class, 'updateReturnStatus'])->name('vat.returns.update-status');
        Route::delete('/vat/returns/{vat_return}', [App\Http\Controllers\VatController::class, 'destroyReturn'])->name('vat.returns.destroy');
        Route::post('/vat/returns/{vat_return}/delete-entries', [App\Http\Controllers\VatController::class, 'deleteReturnEntries'])->name('vat.returns.delete-entries');
        Route::get('/vat/voucher/create', [App\Http\Controllers\VatController::class, 'createVoucher'])->name('vat.voucher.create');
        Route::post('/vat/voucher/store', [App\Http\Controllers\VatController::class, 'storeVoucher'])->name('vat.voucher.store');
        Route::post('accounts/{id}/toggle-lock', [App\Http\Controllers\AccountsController::class, 'toggleLock'])->name('accounts.toggleLock');
        Route::post('accounts/{id}/toggle-status', [App\Http\Controllers\AccountsController::class, 'toggleStatus'])->name('accounts.toggleStatus');
        Route::post('accounts/{id}/toggle-fixed', [App\Http\Controllers\AccountsController::class, 'toggleFixed'])->name('accounts.toggleFixed');
    });

    // Expense module: expense accounts from Chart of Accounts
    Route::get('expenses/detail/{id}', [App\Http\Controllers\ExpenseController::class, 'accountDetail'])->name('expenses.detail');
    Route::get('expenses/detail/{id}/ledger-entries', [App\Http\Controllers\ExpenseController::class, 'ledgerEntries'])->name('expenses.ledgerEntries');
    Route::post('expenses/{id}/toggle-lock', [App\Http\Controllers\ExpenseController::class, 'toggleLock'])->name('expenses.toggleLock');
    Route::post('expenses/{id}/toggle-status', [App\Http\Controllers\ExpenseController::class, 'toggleStatus'])->name('expenses.toggleStatus');
    Route::get('expenses/voucher/create', [App\Http\Controllers\ExpenseController::class, 'createVoucher'])->name('expenses.voucher.create');
    Route::post('expenses/voucher/store', [App\Http\Controllers\ExpenseController::class, 'storeVoucher'])->name('expenses.voucher.store');
    Route::get('expenses/voucher/{id}/edit', [App\Http\Controllers\ExpenseController::class, 'editVoucher'])->name('expenses.voucher.edit');
    Route::put('expenses/voucher/{id}', [App\Http\Controllers\ExpenseController::class, 'updateVoucher'])->name('expenses.voucher.update');
    Route::delete('expenses/voucher/{id}', [App\Http\Controllers\ExpenseController::class, 'destroyVoucher'])->name('expenses.voucher.destroy');
    Route::get('expenses/list-sidebar', [App\Http\Controllers\ExpenseController::class, 'listSidebar'])->name('expenses.list-sidebar');
    Route::get('expenses/voucher/{id}', [App\Http\Controllers\ExpenseController::class, 'showVoucher'])->name('expenses.voucher.show');
    Route::resource('expenses', App\Http\Controllers\ExpenseController::class)->only(['index', 'create', 'store']);
});
Route::group(['prefix' => 'laravel-filemanager', 'middleware' => ['web', 'auth']], function () {
    \UniSharp\LaravelFilemanager\Lfm::routes();
});
/* Route::group(['prefix' => 'laravel-filemanager', 'middleware' => ['web', 'auth']], function () {
  Lfm::routes();
}); */

Route::get('/storage/{folder}/{filename}', [FileController::class, 'show'])->where('filename', '.*');
Route::get('/storage2/{folder}/{filename}', [FileController::class, 'root'])->where('filename', '.*');


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
/* Settings section start here */
Route::prefix('settings')->group(function () {

    Route::any('/company', [HomeController::class, 'settings'])->name('settings');
    Route::get('/settings', [HomeController::class, 'index'])->name('settings.index');
    Route::post('/settings/logo', [HomeController::class, 'updateLogo'])->name('settings.updateLogo');
    Route::post('/settings', [HomeController::class, 'store'])->name('settings.store');
    Route::post('settings/update-favicon', [HomeController::class, 'updateFavicon'])->name('settings.updateFavicon');
    Route::resource('departments', App\Http\Controllers\DepartmentsController::class);
    Route::resource('dropdowns', App\Http\Controllers\DropdownsController::class);
});


/* Suppliers section start here */
Route::prefix('app/{company_slug}')->middleware(['web', 'company.routes', 'tenant', 'auth'])->group(function () {
    // Suppliers: explicit routes before resource (avoid clashes with {supplier})
    Route::get('suppliers/datatable', [SupplierController::class, 'datatable'])->name('suppliers.datatable');
    Route::get('suppliers/trash', [SupplierController::class, 'trash'])->name('suppliers.trash');
    Route::post('suppliers/trash/{id}/restore', [SupplierController::class, 'restoreTrash'])->name('suppliers.restore');
    Route::delete('suppliers/trash/{id}/force-destroy', [SupplierController::class, 'forceDestroyTrash'])->name('suppliers.force-destroy');
    Route::get('suppliers/document/{id}', [SupplierController::class, 'document'])->name('suppliers.document');
    Route::get('suppliers/files/{id}', [SupplierController::class, 'files'])->name('suppliers.files');
    Route::get('suppliers/ledger/{id}', [SupplierController::class, 'ledger'])->name('suppliers.ledger');
    Route::delete('suppliers/delete/{id}', [SupplierController::class, 'destroy'])->name('suppliers.delete');
    Route::get('suppliers/show/{id}', [SupplierController::class, 'show']);
    Route::resource('suppliers', SupplierController::class);

    // Supplier invoices
    Route::resource('supplierInvoices', SupplierInvoicesController::class);
    Route::get('supplier/purchase_orders', [SupplierInvoicesController::class, 'purchaseOrders'])->name('supplier.purchase_order');
    Route::get('supplier/payments', [SupplierInvoicesController::class, 'payments'])->name('supplier.payments');
    Route::any('/supplier_invoices/import', [SupplierInvoicesController::class, 'import'])->name('supplier_invoices.import');
    Route::post('/supplier/invoice/import', [SupplierInvoicesController::class, 'import'])->name('supplier.invoice_import');
    Route::get('/supplier/ledger', [SupplierInvoicesController::class, 'ledger'])->name('supplier.ledger');
    Route::post('/supplier_invoices/send-email/{id}', [SupplierInvoicesController::class, 'sendEmail'])->name('supplier_invoices.send_email');
    Route::put('/supplierInvoices/{id}', [SupplierInvoicesController::class, 'update'])->name('supplierInvoices.update');
    // Route::get('/supplier_invoices/{id}',[SupplierInvoicesController::class, 'edit'])->name('supplier_invoices.edit');
    Route::get('supplierInvoices/edit/{id}', [\App\Http\Controllers\SupplierInvoicesController::class, 'edit'])->name('supplierInvoices.edit');
    Route::post('/supplierInvoices/{id}', [SupplierInvoicesController::class, 'update'])->name('supplierInvoices.update');
    Route::get('/supplier_invoices/{id}', [SupplierInvoicesController::class, 'show'])->name('supplierInvoices.show');
    Route::get('/supplierInvoices/create', [SupplierInvoicesController::class, 'create'])->name('supplierInvoices.create');
    Route::post('supplierInvoices', [SupplierInvoicesController::class, 'store'])->name('supplierInvoices.store');
});

/* Suppliers section end here */
Route::prefix('app/{company_slug}')->middleware(['web', 'company.routes', 'tenant', 'auth'])->group(function () {
    Route::resource('upload_files', UploadFilesController::class);
    Route::get('/upload_files', [UploadFilesController::class, 'index'])->name('upload_files.index');
    Route::get('/upload_files/create', [UploadFilesController::class, 'create'])->name('upload_files.create');
    Route::post('/upload_files', [UploadFilesController::class, 'store'])->name('upload_files.store');
    Route::get('/upload_files/{id}', [UploadFilesController::class, 'show'])->name('upload_files.show');
    Route::get('/upload_files/{id}/edit', [UploadFilesController::class, 'edit'])->name('upload_files.edit');
    Route::put('/upload_files/{id}', [UploadFilesController::class, 'update'])->name('upload_files.update');
    Route::delete('/upload_files/{id}', [UploadFilesController::class, 'destroy'])->name('upload_files.destroy');
});



Route::prefix('app/{company_slug}')->middleware(['web', 'company.routes', 'tenant', 'auth'])->group(function () {
    // Specific Salik routes (must come before resource route)
    Route::get('salik/missing-records', [\App\Http\Controllers\SalikController::class, 'showMissingRecords'])->name('salik.missing.records');
    Route::get('salik/export-missing-records', [\App\Http\Controllers\SalikController::class, 'exportMissingRecords'])->name('salik.export.missing.records');
    Route::post('salik/analyze-excel', [\App\Http\Controllers\SalikController::class, 'analyzeExcelFile'])->name('salik.analyze.excel');
    Route::any('salik/clear-failed-imports', [\App\Http\Controllers\SalikController::class, 'clearFailedImports'])->name('salik.clear.failed.imports');
    Route::get('salik/import/{salik_account_id}', [\App\Http\Controllers\SalikController::class, 'importForm'])->name('salik.import.form');
    Route::post('salik/import', [\App\Http\Controllers\SalikController::class, 'import'])->name('salik.import');
    Route::post('salik/test-import', [\App\Http\Controllers\SalikController::class, 'testImport'])->name('salik.test.import');

    // Salik resource routes
    Route::resource('salik', App\Http\Controllers\SalikController::class);
    Route::post('salik/store', [\App\Http\Controllers\SalikController::class, 'store'])->name('salik.store');
    Route::get('salik/edit/{id}', [\App\Http\Controllers\SalikController::class, 'edit'])->name('salik.edit');
    Route::post('/salik/{id}/update', [SalikController::class, 'update'])->name('salik.update');
    Route::get('salik/create/{id}', [\App\Http\Controllers\SalikController::class, 'create'])->name('salik.create');
    Route::any('salik/attach_file/{id}', [\App\Http\Controllers\SalikController::class, 'fileUpload'])->name('salik.fileupload');
    Route::get('salik/delete/{id}', [\App\Http\Controllers\SalikController::class, 'destroy'])->name('salik.delete');

    Route::post('salik/accountcreate', [\App\Http\Controllers\SalikController::class, 'accountcreate'])->name('salik.accountcreate');
    Route::post('salik/editaccount', [\App\Http\Controllers\SalikController::class, 'editaccount'])->name('salik.editaccount');
    Route::get('salik/deleteaccount/{id}', [\App\Http\Controllers\SalikController::class, 'deleteaccount'])->name('salik.deleteaccount');
    Route::get('salik/tickets/{id}', [\App\Http\Controllers\SalikController::class, 'tickets'])->name('salik.tickets');
    Route::get('salik/viewvoucher/{id}', [\App\Http\Controllers\SalikController::class, 'viewvoucher'])->name('salik.viewvoucher');
    Route::post('salik/getriderbybikedate', [SalikController::class, 'getriderbybikedate'])->name('salik.getriderbybikedate');
});
