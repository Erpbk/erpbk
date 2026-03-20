<?php

/**
 * Settings panel routes. Loaded under /app/{company_slug}/ so tenant DB + auth work.
 * Route names stay settings-panel.* — use URL::defaults(['company_slug' => ...]) in tenant middleware.
 */

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;

// Settings Panel (opens in separate window, Zoho-style admin)
Route::prefix('settings-panel')->middleware('settings.panel')->group(function () {
    Route::get('/', [App\Http\Controllers\SettingsPanelController::class, 'index'])->name('settings-panel.index');
    Route::match(['get', 'post'], '/company', [HomeController::class, 'settings'])->name('settings-panel.company');
    Route::get('/erp', [App\Http\Controllers\ErpSettingsController::class, 'index'])->name('settings-panel.erp');
    Route::post('/erp', [App\Http\Controllers\ErpSettingsController::class, 'store'])->name('settings-panel.erp.store');

    // Profile + Email Settings (inside Settings Panel)
    Route::any('/profile', [\App\Http\Controllers\UserController::class, 'profile'])->name('settings-panel.profile');
    Route::get('/email-settings', [\App\Http\Controllers\UserEmailSettingsController::class, 'edit'])->name('settings-panel.email-settings.edit');
    Route::post('/email-settings', [\App\Http\Controllers\UserEmailSettingsController::class, 'update'])->name('settings-panel.email-settings.update');

    Route::get('vat-settings', [App\Http\Controllers\VatSettingsController::class, 'index'])->name('settings-panel.vat-settings.index');
    Route::post('vat-settings/module-label', [App\Http\Controllers\VatSettingsController::class, 'storeModuleLabel'])->name('settings-panel.vat-settings.store-module-label');
    Route::post('vat-settings/quarters', [App\Http\Controllers\VatSettingsController::class, 'storeQuarter'])->name('settings-panel.vat-settings.store-quarter');
    Route::delete('vat-settings/quarters/{slot}', [App\Http\Controllers\VatSettingsController::class, 'deleteQuarter'])->name('settings-panel.vat-settings.delete-quarter');
    Route::post('vat-settings', [App\Http\Controllers\VatSettingsController::class, 'store'])->name('settings-panel.vat-settings.store');
    Route::resource('departments', App\Http\Controllers\DepartmentsController::class)->names('settings-panel.departments');
    Route::resource('dropdowns', App\Http\Controllers\DropdownsController::class)->names('settings-panel.dropdowns');
    Route::resource('visa-statuses', App\Http\Controllers\VisaStatusController::class)->names('settings-panel.visa-statuses');
    Route::post('visa-statuses/reorder', [App\Http\Controllers\VisaStatusController::class, 'reorder'])->name('settings-panel.visa-statuses.reorder');
    Route::get('visa-statuses/{id}/toggle-active', [App\Http\Controllers\VisaStatusController::class, 'toggleActive'])->name('settings-panel.visa-statuses.toggle-active');
    Route::resource('branches', App\Http\Controllers\BranchController::class)->names('settings-panel.branches');
    // Account field settings (fixed + custom fields; only custom are editable/deletable)
    Route::get('account-fields', [App\Http\Controllers\AccountFieldSettingsController::class, 'index'])->name('settings-panel.account-fields.index');
    Route::post('account-fields/module-label', [App\Http\Controllers\AccountFieldSettingsController::class, 'storeModuleLabel'])->name('settings-panel.account-fields.store-module-label');
    Route::get('account-fields/table-body', [App\Http\Controllers\AccountFieldSettingsController::class, 'tableBody'])->name('settings-panel.account-fields.table-body');
    Route::get('account-fields/config-schema/{dataType}', [App\Http\Controllers\AccountFieldSettingsController::class, 'configSchema'])->name('settings-panel.account-fields.config-schema');
    Route::post('account-fields', [App\Http\Controllers\AccountFieldSettingsController::class, 'store'])->name('settings-panel.account-fields.store');
    Route::put('account-fields/{id}', [App\Http\Controllers\AccountFieldSettingsController::class, 'update'])->name('settings-panel.account-fields.update');
    Route::delete('account-fields/{id}', [App\Http\Controllers\AccountFieldSettingsController::class, 'destroy'])->name('settings-panel.account-fields.destroy');
    Route::post('account-fields/reorder', [App\Http\Controllers\AccountFieldSettingsController::class, 'reorder'])->name('settings-panel.account-fields.reorder');
    // Voucher Settings (voucher types + voucher custom fields)
    Route::get('voucher-settings', [App\Http\Controllers\VoucherSettingsController::class, 'index'])->name('settings-panel.voucher-settings.index');
    Route::post('voucher-settings/module-label', [App\Http\Controllers\VoucherSettingsController::class, 'storeModuleLabel'])->name('settings-panel.voucher-settings.store-module-label');
    Route::get('voucher-settings/types/table-body', [App\Http\Controllers\VoucherSettingsController::class, 'typesTableBody'])->name('settings-panel.voucher-settings.types-table-body');
    Route::post('voucher-settings/types', [App\Http\Controllers\VoucherSettingsController::class, 'storeType'])->name('settings-panel.voucher-settings.store-type');
    Route::put('voucher-settings/types/{id}', [App\Http\Controllers\VoucherSettingsController::class, 'updateType'])->name('settings-panel.voucher-settings.update-type');
    Route::delete('voucher-settings/types/{id}', [App\Http\Controllers\VoucherSettingsController::class, 'destroyType'])->name('settings-panel.voucher-settings.destroy-type');
    Route::post('voucher-settings/types/reorder', [App\Http\Controllers\VoucherSettingsController::class, 'reorderTypes'])->name('settings-panel.voucher-settings.reorder-types');
    Route::get('voucher-settings/fields/table-body', [App\Http\Controllers\VoucherSettingsController::class, 'fieldsTableBody'])->name('settings-panel.voucher-settings.fields-table-body');
    Route::get('voucher-settings/fields/config-schema/{dataType}', [App\Http\Controllers\VoucherSettingsController::class, 'fieldConfigSchema'])->name('settings-panel.voucher-settings.field-config-schema');
    Route::post('voucher-settings/fields', [App\Http\Controllers\VoucherSettingsController::class, 'storeField'])->name('settings-panel.voucher-settings.store-field');
    Route::put('voucher-settings/fields/{id}', [App\Http\Controllers\VoucherSettingsController::class, 'updateField'])->name('settings-panel.voucher-settings.update-field');
    Route::delete('voucher-settings/fields/{id}', [App\Http\Controllers\VoucherSettingsController::class, 'destroyField'])->name('settings-panel.voucher-settings.destroy-field');
    Route::post('voucher-settings/fields/reorder', [App\Http\Controllers\VoucherSettingsController::class, 'reorderFields'])->name('settings-panel.voucher-settings.reorder-fields');
    // Rider Settings (categories + fixed rider fields + rider custom fields)
    Route::get('rider-settings', [App\Http\Controllers\RiderSettingsController::class, 'index'])->name('settings-panel.rider-settings.index');
    Route::post('rider-settings/module-label', [App\Http\Controllers\RiderSettingsController::class, 'storeModuleLabel'])->name('settings-panel.rider-settings.store-module-label');
    Route::post('rider-settings/field-assignment', [App\Http\Controllers\RiderSettingsController::class, 'updateFieldAssignment'])->name('settings-panel.rider-settings.update-field-assignment');
    Route::post('rider-settings/field-assignment/display-label', [App\Http\Controllers\RiderSettingsController::class, 'updateFieldAssignmentLabel'])->name('settings-panel.rider-settings.update-field-assignment-label');
    Route::post('rider-settings/field-assignment/visibility', [App\Http\Controllers\RiderSettingsController::class, 'updateFieldAssignmentVisibility'])->name('settings-panel.rider-settings.update-field-assignment-visibility');
    Route::post('rider-settings/field-assignments/reorder', [App\Http\Controllers\RiderSettingsController::class, 'reorderFieldAssignments'])->name('settings-panel.rider-settings.reorder-field-assignments');
    Route::get('rider-settings/categories/table-body', [App\Http\Controllers\RiderSettingsController::class, 'categoriesTableBody'])->name('settings-panel.rider-settings.categories-table-body');
    Route::post('rider-settings/categories', [App\Http\Controllers\RiderSettingsController::class, 'storeCategory'])->name('settings-panel.rider-settings.store-category');
    Route::put('rider-settings/categories/{id}', [App\Http\Controllers\RiderSettingsController::class, 'updateCategory'])->name('settings-panel.rider-settings.update-category');
    Route::delete('rider-settings/categories/{id}', [App\Http\Controllers\RiderSettingsController::class, 'destroyCategory'])->name('settings-panel.rider-settings.destroy-category');
    Route::post('rider-settings/categories/reorder', [App\Http\Controllers\RiderSettingsController::class, 'reorderCategories'])->name('settings-panel.rider-settings.reorder-categories');
    Route::get('rider-settings/fields/table-body', [App\Http\Controllers\RiderSettingsController::class, 'tableBody'])->name('settings-panel.rider-settings.table-body');
    Route::get('rider-settings/fields/table-body/{categoryId}', [App\Http\Controllers\RiderSettingsController::class, 'tableBodyCategory'])->name('settings-panel.rider-settings.table-body-category');
    Route::get('rider-settings/fields/config-schema/{dataType}', [App\Http\Controllers\RiderSettingsController::class, 'fieldConfigSchema'])->name('settings-panel.rider-settings.field-config-schema');
    Route::post('rider-settings/fields', [App\Http\Controllers\RiderSettingsController::class, 'storeField'])->name('settings-panel.rider-settings.store-field');
    Route::put('rider-settings/fields/{id}', [App\Http\Controllers\RiderSettingsController::class, 'updateField'])->name('settings-panel.rider-settings.update-field');
    Route::delete('rider-settings/fields/{id}', [App\Http\Controllers\RiderSettingsController::class, 'destroyField'])->name('settings-panel.rider-settings.destroy-field');
    Route::post('rider-settings/fields/reorder', [App\Http\Controllers\RiderSettingsController::class, 'reorderFields'])->name('settings-panel.rider-settings.reorder-fields');
    Route::get('rider-settings/documents/table-body', [App\Http\Controllers\RiderSettingsController::class, 'documentTypesTableBody'])->name('settings-panel.rider-settings.document-types-table-body');
    Route::post('rider-settings/documents', [App\Http\Controllers\RiderSettingsController::class, 'storeDocumentType'])->name('settings-panel.rider-settings.store-document-type');
    Route::put('rider-settings/documents/{id}', [App\Http\Controllers\RiderSettingsController::class, 'updateDocumentType'])->name('settings-panel.rider-settings.update-document-type');
    Route::delete('rider-settings/documents/{id}', [App\Http\Controllers\RiderSettingsController::class, 'destroyDocumentType'])->name('settings-panel.rider-settings.destroy-document-type');
    Route::post('rider-settings/documents/reorder', [App\Http\Controllers\RiderSettingsController::class, 'reorderDocumentTypes'])->name('settings-panel.rider-settings.reorder-document-types');
    // Module settings (General tab only) for all ERP modules
    Route::get('module-settings/{module}', [App\Http\Controllers\ModuleSettingsController::class, 'index'])->name('settings-panel.module-settings.index')->where('module', '[a-z_]+');
    Route::post('module-settings/{module}/module-label', [App\Http\Controllers\ModuleSettingsController::class, 'storeModuleLabel'])->name('settings-panel.module-settings.store-module-label')->where('module', '[a-z_]+');
    // User Management, Activity Logs, Recycle Bin (moved into Settings)

    Route::resource('users', App\Http\Controllers\UserController::class)->names('settings-panel.users');
    Route::patch('users/{id}/password', [App\Http\Controllers\UserController::class, 'changePassword'])->name('users.password');
    Route::resource('permissions', App\Http\Controllers\PermissionsController::class)->names('settings-panel.permissions');
    Route::resource('roles', App\Http\Controllers\RolesController::class)->names('settings-panel.roles');
    Route::prefix('activity-logs')->name('settings-panel.activity-logs.')->group(function () {
        Route::get('/', [ActivityLogController::class, 'index'])->name('index');
        Route::get('/api/statistics', [ActivityLogController::class, 'statistics'])->name('statistics');
        Route::get('/{activityLog}', [ActivityLogController::class, 'show'])->name('show');
    });
    Route::prefix('trash')->name('settings-panel.trash.')->group(function () {
        Route::get('/', [App\Http\Controllers\TrashController::class, 'index'])->name('index');
        Route::get('/stats', [App\Http\Controllers\TrashController::class, 'stats'])->name('stats');
        Route::get('/{module}/{id}/show', [App\Http\Controllers\TrashController::class, 'show'])->name('show');
        Route::post('/{module}/{id}/restore', [App\Http\Controllers\TrashController::class, 'restore'])->name('restore');
        Route::delete('/{module}/{id}/force-destroy', [App\Http\Controllers\TrashController::class, 'forceDestroy'])->name('force-destroy');
    });
});
