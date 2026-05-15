<?php

/**
 * Settings panel routes. Loaded under /app/{company_slug}/ so tenant DB + auth work.
 * Route names stay settings-panel.* — use URL::defaults(['company_slug' => ...]) in tenant middleware.
 */

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\HomeController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Backward-compatibility alias: redirect old typo URLs (/settings-pane/*) to /settings-panel/*.
Route::get('settings-pane/{path?}', function (Request $request, ?string $path = null) {
    $companySlug = $request->route('company_slug') ?? session('company_slug');
    return redirect()->to(url('app/' . $companySlug . '/settings-panel/' . ltrim((string) $path, '/')));
})->where('path', '.*');

// Settings Panel (opens in separate window, Zoho-style admin)
Route::prefix('settings-panel')->middleware(['settings.panel', 'company.settings'])->group(function () {
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
    Route::post('bike-registration-statuses/reorder', [App\Http\Controllers\BikeRegistrationStatusController::class, 'reorder'])->name('settings-panel.bike-registration-statuses.reorder');
    Route::get('bike-registration-statuses/{id}/toggle-active', [App\Http\Controllers\BikeRegistrationStatusController::class, 'toggleActive'])->name('settings-panel.bike-registration-statuses.toggle-active');
    Route::resource('bike-registration-statuses', App\Http\Controllers\BikeRegistrationStatusController::class)->names('settings-panel.bike-registration-statuses');
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
    Route::post('rider-settings/field-assignment/required', [App\Http\Controllers\RiderSettingsController::class, 'updateFieldAssignmentRequired'])->name('settings-panel.rider-settings.update-field-assignment-required');
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
    Route::post('rider-settings/fields/{id}/assign-category', [App\Http\Controllers\RiderSettingsController::class, 'assignCustomFieldCategory'])->name('settings-panel.rider-settings.assign-custom-field-category');
    Route::put('rider-settings/fields/{id}', [App\Http\Controllers\RiderSettingsController::class, 'updateField'])->name('settings-panel.rider-settings.update-field');
    Route::delete('rider-settings/fields/{id}', [App\Http\Controllers\RiderSettingsController::class, 'destroyField'])->name('settings-panel.rider-settings.destroy-field');
    Route::post('rider-settings/fields/{id}/flags', [App\Http\Controllers\RiderSettingsController::class, 'updateCustomFieldFlags'])->name('settings-panel.rider-settings.update-custom-field-flags');
    Route::post('rider-settings/fields/reorder', [App\Http\Controllers\RiderSettingsController::class, 'reorderFields'])->name('settings-panel.rider-settings.reorder-fields');
    Route::get('rider-settings/documents/table-body', [App\Http\Controllers\RiderSettingsController::class, 'documentTypesTableBody'])->name('settings-panel.rider-settings.document-types-table-body');
    Route::post('rider-settings/documents', [App\Http\Controllers\RiderSettingsController::class, 'storeDocumentType'])->name('settings-panel.rider-settings.store-document-type');
    Route::put('rider-settings/documents/{id}', [App\Http\Controllers\RiderSettingsController::class, 'updateDocumentType'])->name('settings-panel.rider-settings.update-document-type');
    Route::delete('rider-settings/documents/{id}', [App\Http\Controllers\RiderSettingsController::class, 'destroyDocumentType'])->name('settings-panel.rider-settings.destroy-document-type');
    Route::post('rider-settings/documents/reorder', [App\Http\Controllers\RiderSettingsController::class, 'reorderDocumentTypes'])->name('settings-panel.rider-settings.reorder-document-types');
    Route::get('rider-settings/rider-top/accordion-body', [App\Http\Controllers\RiderSettingsController::class, 'riderTopAccordionBody'])->name('settings-panel.rider-settings.rider-top-accordion-body');
    Route::post('rider-settings/rider-top/categories', [App\Http\Controllers\RiderSettingsController::class, 'storeRiderTopCategory'])->name('settings-panel.rider-settings.store-rider-top-category');
    Route::get('rider-settings/rider-top/categories/{id}/field-values', [App\Http\Controllers\RiderSettingsController::class, 'riderTopCategoryFieldValues'])->name('settings-panel.rider-settings.rider-top-category-field-values');
    Route::put('rider-settings/rider-top/categories/{id}', [App\Http\Controllers\RiderSettingsController::class, 'updateRiderTopCategory'])->name('settings-panel.rider-settings.update-rider-top-category');
    Route::delete('rider-settings/rider-top/categories/{id}', [App\Http\Controllers\RiderSettingsController::class, 'destroyRiderTopCategory'])->name('settings-panel.rider-settings.destroy-rider-top-category');
    Route::post('rider-settings/rider-top/categories/{id}/visibility', [App\Http\Controllers\RiderSettingsController::class, 'updateRiderTopCategoryVisibility'])->name('settings-panel.rider-settings.update-rider-top-category-visibility');
    Route::post('rider-settings/rider-top/options', [App\Http\Controllers\RiderSettingsController::class, 'storeRiderTopOption'])->name('settings-panel.rider-settings.store-rider-top-option');
    Route::put('rider-settings/rider-top/options/{id}', [App\Http\Controllers\RiderSettingsController::class, 'updateRiderTopOption'])->name('settings-panel.rider-settings.update-rider-top-option');
    Route::delete('rider-settings/rider-top/options/{id}', [App\Http\Controllers\RiderSettingsController::class, 'destroyRiderTopOption'])->name('settings-panel.rider-settings.destroy-rider-top-option');
    Route::post('rider-settings/statuses', [App\Http\Controllers\RiderSettingsController::class, 'storeRiderStatus'])->name('settings-panel.rider-settings.store-rider-status');
    Route::put('rider-settings/statuses/{id}', [App\Http\Controllers\RiderSettingsController::class, 'updateRiderStatus'])->name('settings-panel.rider-settings.update-rider-status');
    Route::delete('rider-settings/statuses/{id}', [App\Http\Controllers\RiderSettingsController::class, 'destroyRiderStatus'])->name('settings-panel.rider-settings.destroy-rider-status');
    // Backward-compatible alias: rider-settings lives on dedicated controller page.
    Route::get('module-settings/rider-settings', function (\Illuminate\Http\Request $request) {
        $companySlug = (string) ($request->route('company_slug') ?? '');
        return redirect()->route(
            'settings-panel.rider-settings.index',
            array_merge(['company_slug' => $companySlug], $request->query())
        );
    })->name('settings-panel.module-settings.rider-settings-alias');

    // Employee Settings (separate module-settings stack using module_key=employees)
    Route::get('employee-settings', function (\Illuminate\Http\Request $request) {
        $companySlug = (string) ($request->route('company_slug') ?? '');
        return redirect()->route(
            'settings-panel.module-settings.index',
            array_merge(['company_slug' => $companySlug, 'module' => 'employees'], $request->query())
        );
    })->name('settings-panel.employee-settings.index');

    // Bike Settings: mount under module-settings/bike_list
    // (So the sidebar route `settings-panel/module-settings/bike_list` opens bike settings.)
    Route::post('module-settings/bike_list/module-label', [App\Http\Controllers\BikeSettingsController::class, 'storeModuleLabel'])->name('settings-panel.bike-settings.store-module-label');

    Route::post('module-settings/bike_list/field-assignment', [App\Http\Controllers\BikeSettingsController::class, 'updateFieldAssignment'])->name('settings-panel.bike-settings.update-field-assignment');
    Route::post('module-settings/bike_list/field-assignments/reorder', [App\Http\Controllers\BikeSettingsController::class, 'reorderFieldAssignments'])->name('settings-panel.bike-settings.reorder-field-assignments');
    Route::post('module-settings/bike_list/field-assignments/reorder-all', [App\Http\Controllers\BikeSettingsController::class, 'reorderAllFieldAssignments'])->name('settings-panel.bike-settings.reorder-field-assignments-all');
    Route::post('module-settings/bike_list/categories', [App\Http\Controllers\BikeSettingsController::class, 'storeCategory'])->name('settings-panel.bike-settings.store-category');
    Route::put('module-settings/bike_list/categories/{id}', [App\Http\Controllers\BikeSettingsController::class, 'updateCategory'])->name('settings-panel.bike-settings.update-category');
    Route::delete('module-settings/bike_list/categories/{id}', [App\Http\Controllers\BikeSettingsController::class, 'destroyCategory'])->name('settings-panel.bike-settings.destroy-category');

    // Bike custom fields
    Route::post('module-settings/bike_list/fields', [App\Http\Controllers\BikeSettingsController::class, 'storeField'])->name('settings-panel.bike-settings.store-field');
    Route::post('module-settings/bike_list/fields/reorder', [App\Http\Controllers\BikeSettingsController::class, 'reorderFields'])->name('settings-panel.bike-settings.reorder-fields');
    Route::post('module-settings/bike_list/fields/reorder-all', [App\Http\Controllers\BikeSettingsController::class, 'reorderAllCustomFields'])->name('settings-panel.bike-settings.reorder-all-custom-fields');
    Route::put('module-settings/bike_list/fields/{id}', [App\Http\Controllers\BikeSettingsController::class, 'updateField'])->name('settings-panel.bike-settings.update-field');
    Route::delete('module-settings/bike_list/fields/{id}', [App\Http\Controllers\BikeSettingsController::class, 'destroyField'])->name('settings-panel.bike-settings.destroy-field');
    Route::post('module-settings/bike_list/assign-custom-field-category/{id}', [App\Http\Controllers\BikeSettingsController::class, 'assignCustomFieldCategory'])->name('settings-panel.bike-settings.assign-custom-field-category');

    Route::post('module-settings/bike_list/assign-fields', [App\Http\Controllers\BikeSettingsController::class, 'updateAssignFieldAssignment'])->name('settings-panel.bike-settings.update-assign-field');
    Route::put('module-settings/bike_list/assign-fields/{id}', [App\Http\Controllers\BikeSettingsController::class, 'updateAssignField'])->name('settings-panel.bike-settings.update-assign-field-item');
    Route::post('module-settings/bike_list/assign-fields/reorder', [App\Http\Controllers\BikeSettingsController::class, 'reorderAssignFieldAssignments'])->name('settings-panel.bike-settings.reorder-assign-fields');
    Route::post('module-settings/bike_list/assign-fields/store', [App\Http\Controllers\BikeSettingsController::class, 'storeAssignField'])->name('settings-panel.bike-settings.store-assign-field');
    Route::delete('module-settings/bike_list/assign-fields/{id}', [App\Http\Controllers\BikeSettingsController::class, 'destroyAssignField'])->name('settings-panel.bike-settings.destroy-assign-field');

    // Bike documents
    Route::post('module-settings/bike_list/documents', [App\Http\Controllers\BikeSettingsController::class, 'storeDocumentType'])->name('settings-panel.bike-settings.store-document-type');
    Route::put('module-settings/bike_list/documents/{id}', [App\Http\Controllers\BikeSettingsController::class, 'updateDocumentType'])->name('settings-panel.bike-settings.update-document-type');
    Route::delete('module-settings/bike_list/documents/{id}', [App\Http\Controllers\BikeSettingsController::class, 'destroyDocumentType'])->name('settings-panel.bike-settings.destroy-document-type');

    Route::get('module-settings/bike_list/bike-top/accordion-body', [App\Http\Controllers\BikeSettingsController::class, 'bikeTopAccordionBody'])->name('settings-panel.bike-settings.bike-top-accordion-body');
    Route::post('module-settings/bike_list/bike-top/categories', [App\Http\Controllers\BikeSettingsController::class, 'storeBikeTopCategory'])->name('settings-panel.bike-settings.store-bike-top-category');
    Route::get('module-settings/bike_list/bike-top/categories/{id}/field-values', [App\Http\Controllers\BikeSettingsController::class, 'bikeTopCategoryFieldValues'])->name('settings-panel.bike-settings.bike-top-category-field-values');
    Route::put('module-settings/bike_list/bike-top/categories/{id}', [App\Http\Controllers\BikeSettingsController::class, 'updateBikeTopCategory'])->name('settings-panel.bike-settings.update-bike-top-category');
    Route::delete('module-settings/bike_list/bike-top/categories/{id}', [App\Http\Controllers\BikeSettingsController::class, 'destroyBikeTopCategory'])->name('settings-panel.bike-settings.destroy-bike-top-category');
    Route::post('module-settings/bike_list/bike-top/categories/{id}/visibility', [App\Http\Controllers\BikeSettingsController::class, 'updateBikeTopCategoryVisibility'])->name('settings-panel.bike-settings.update-bike-top-category-visibility');
    Route::post('module-settings/bike_list/bike-top/options', [App\Http\Controllers\BikeSettingsController::class, 'storeBikeTopOption'])->name('settings-panel.bike-settings.store-bike-top-option');
    Route::put('module-settings/bike_list/bike-top/options/{id}', [App\Http\Controllers\BikeSettingsController::class, 'updateBikeTopOption'])->name('settings-panel.bike-settings.update-bike-top-option');
    Route::delete('module-settings/bike_list/bike-top/options/{id}', [App\Http\Controllers\BikeSettingsController::class, 'destroyBikeTopOption'])->name('settings-panel.bike-settings.destroy-bike-top-option');
    Route::post('module-settings/bike_list/bike-top/user-preferences', [App\Http\Controllers\BikeSettingsController::class, 'saveBikeTopUserPreferences'])->name('settings-panel.bike-settings.save-bike-top-user-preferences');

    // Module settings for all ERP modules (Bike-style route pattern)
    Route::post('module-settings/{module}/field-assignment', [App\Http\Controllers\ModuleSettingsController::class, 'storeFieldAssignment'])->name('settings-panel.module-settings.update-field-assignment')->where('module', '[A-Za-z0-9_-]+');
    Route::post('module-settings/{module}/field-assignments/reorder', [App\Http\Controllers\ModuleSettingsController::class, 'reorderFieldAssignments'])->name('settings-panel.module-settings.reorder-field-assignments')->where('module', '[A-Za-z0-9_-]+');
    Route::post('module-settings/{module}/field-assignments/reorder-all', [App\Http\Controllers\ModuleSettingsController::class, 'reorderAllFieldAssignments'])->name('settings-panel.module-settings.reorder-field-assignments-all')->where('module', '[A-Za-z0-9_-]+');
    Route::post('module-settings/{module}/categories', [App\Http\Controllers\ModuleSettingsController::class, 'storeCategory'])->name('settings-panel.module-settings.store-category')->where('module', '[A-Za-z0-9_-]+');
    Route::put('module-settings/{module}/categories/{id}', [App\Http\Controllers\ModuleSettingsController::class, 'updateCategory'])->name('settings-panel.module-settings.update-category')->where('module', '[A-Za-z0-9_-]+');
    Route::delete('module-settings/{module}/categories/{id}', [App\Http\Controllers\ModuleSettingsController::class, 'destroyCategory'])->name('settings-panel.module-settings.destroy-category')->where('module', '[A-Za-z0-9_-]+');
    Route::post('module-settings/{module}/fields', [App\Http\Controllers\ModuleSettingsController::class, 'storeField'])->name('settings-panel.module-settings.store-field')->where('module', '[A-Za-z0-9_-]+');
    Route::post('module-settings/{module}/fields/reorder', [App\Http\Controllers\ModuleSettingsController::class, 'reorderFields'])->name('settings-panel.module-settings.reorder-fields')->where('module', '[A-Za-z0-9_-]+');
    Route::post('module-settings/{module}/fields/reorder-all', [App\Http\Controllers\ModuleSettingsController::class, 'reorderAllCustomFields'])->name('settings-panel.module-settings.reorder-all-custom-fields')->where('module', '[A-Za-z0-9_-]+');
    Route::post('module-settings/{module}/assign-custom-field-category/{id}', [App\Http\Controllers\ModuleSettingsController::class, 'assignCustomFieldCategory'])->name('settings-panel.module-settings.assign-custom-field-category')->where('module', '[A-Za-z0-9_-]+');
    Route::put('module-settings/{module}/fields/{id}', [App\Http\Controllers\ModuleSettingsController::class, 'updateField'])->name('settings-panel.module-settings.update-field')->where('module', '[A-Za-z0-9_-]+');
    Route::delete('module-settings/{module}/fields/{id}', [App\Http\Controllers\ModuleSettingsController::class, 'destroyField'])->name('settings-panel.module-settings.destroy-field')->where('module', '[A-Za-z0-9_-]+');
    Route::post('module-settings/{module}/documents', [App\Http\Controllers\ModuleSettingsController::class, 'storeDocumentType'])->name('settings-panel.module-settings.store-document-type')->where('module', '[A-Za-z0-9_-]+');
    Route::put('module-settings/{module}/documents/{id}', [App\Http\Controllers\ModuleSettingsController::class, 'updateDocumentType'])->name('settings-panel.module-settings.update-document-type')->where('module', '[A-Za-z0-9_-]+');
    Route::delete('module-settings/{module}/documents/{id}', [App\Http\Controllers\ModuleSettingsController::class, 'destroyDocumentType'])->name('settings-panel.module-settings.destroy-document-type')->where('module', '[A-Za-z0-9_-]+');
    Route::post('module-settings/{module}/visa-expense-top', [App\Http\Controllers\ModuleSettingsController::class, 'updateVisaExpenseTop'])->name('settings-panel.module-settings.update-visa-expense-top')->where('module', '[A-Za-z0-9_-]+');
    Route::post('module-settings/{module}/bike-registration-top', [App\Http\Controllers\ModuleSettingsController::class, 'updateBikeRegistrationTop'])->name('settings-panel.module-settings.update-bike-registration-top')->where('module', '[A-Za-z0-9_-]+');

    // Module settings page + label update
    Route::get('module-settings/{module}', [App\Http\Controllers\ModuleSettingsController::class, 'index'])->name('settings-panel.module-settings.index')->where('module', '[A-Za-z0-9_-]+');
    Route::post('module-settings/{module}/module-label', [App\Http\Controllers\ModuleSettingsController::class, 'storeModuleLabel'])->name('settings-panel.module-settings.store-module-label')->where('module', '[A-Za-z0-9_-]+');
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
