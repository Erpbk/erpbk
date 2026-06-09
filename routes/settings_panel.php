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
    Route::post('/company/email/send-otp', [\App\Http\Controllers\Company\CompanyEmailChangeController::class, 'sendOtp'])->name('settings-panel.company.email.send-otp');
    Route::post('/company/email/verify-otp', [\App\Http\Controllers\Company\CompanyEmailChangeController::class, 'verifyOtp'])->name('settings-panel.company.email.verify-otp');
    Route::get('/erp', [App\Http\Controllers\ErpSettingsController::class, 'index'])->name('settings-panel.erp');
    Route::post('/erp', [App\Http\Controllers\ErpSettingsController::class, 'store'])->name('settings-panel.erp.store');

    Route::get('menu-icons/library/search', [App\Http\Controllers\ModuleMenuIconController::class, 'search'])->name('settings-panel.menu-icons.library-search');
    Route::post('menu-icons/library/save', [App\Http\Controllers\ModuleMenuIconController::class, 'store'])->name('settings-panel.menu-icons.library-save');
    Route::post('menu-icons/library/upload', [App\Http\Controllers\ModuleMenuIconController::class, 'storeImage'])->name('settings-panel.menu-icons.library-upload');

    // Profile + Email Settings (inside Settings Panel)
    Route::any('/profile', [\App\Http\Controllers\UserController::class, 'profile'])->name('settings-panel.profile');
    Route::get('/email-settings', [\App\Http\Controllers\UserEmailSettingsController::class, 'edit'])->name('settings-panel.email-settings.edit');
    Route::post('/email-settings', [\App\Http\Controllers\UserEmailSettingsController::class, 'update'])->name('settings-panel.email-settings.update');

    Route::get('vat-settings', [App\Http\Controllers\VatSettingsController::class, 'index'])->name('settings-panel.vat-settings.index');
    Route::post('vat-settings/module-label', [App\Http\Controllers\VatSettingsController::class, 'storeModuleLabel'])->name('settings-panel.vat-settings.store-module-label');
    Route::post('vat-settings/module-icon', [App\Http\Controllers\VatSettingsController::class, 'storeModuleIcon'])->name('settings-panel.vat-settings.store-module-icon');
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
    Route::post('account-fields/module-icon', [App\Http\Controllers\AccountFieldSettingsController::class, 'storeModuleIcon'])->name('settings-panel.account-fields.store-module-icon');
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
    Route::post('rider-settings/module-icon', [App\Http\Controllers\RiderSettingsController::class, 'storeModuleIcon'])->name('settings-panel.rider-settings.store-module-icon');
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

    // Cheques Settings (categories, module fields, documents, top bar)
    Route::get('cheques-settings', [App\Http\Controllers\ChequesSettingsController::class, 'index'])->name('settings-panel.cheques-settings.index');
    Route::post('cheques-settings/module-label', [App\Http\Controllers\ChequesSettingsController::class, 'storeModuleLabel'])->name('settings-panel.cheques-settings.store-module-label');
    Route::post('cheques-settings/module-icon', [App\Http\Controllers\ChequesSettingsController::class, 'storeModuleIcon'])->name('settings-panel.cheques-settings.store-module-icon');
    Route::post('cheques-settings/field-assignment', [App\Http\Controllers\ChequesSettingsController::class, 'updateFieldAssignment'])->name('settings-panel.cheques-settings.update-field-assignment');
    Route::post('cheques-settings/field-assignment/display-label', [App\Http\Controllers\ChequesSettingsController::class, 'updateFieldAssignmentLabel'])->name('settings-panel.cheques-settings.update-field-assignment-label');
    Route::post('cheques-settings/field-assignment/visibility', [App\Http\Controllers\ChequesSettingsController::class, 'updateFieldAssignmentVisibility'])->name('settings-panel.cheques-settings.update-field-assignment-visibility');
    Route::post('cheques-settings/field-assignment/required', [App\Http\Controllers\ChequesSettingsController::class, 'updateFieldAssignmentRequired'])->name('settings-panel.cheques-settings.update-field-assignment-required');
    Route::post('cheques-settings/field-assignments/reorder', [App\Http\Controllers\ChequesSettingsController::class, 'reorderFieldAssignments'])->name('settings-panel.cheques-settings.reorder-field-assignments');
    Route::get('cheques-settings/categories/table-body', [App\Http\Controllers\ChequesSettingsController::class, 'categoriesTableBody'])->name('settings-panel.cheques-settings.categories-table-body');
    Route::post('cheques-settings/categories', [App\Http\Controllers\ChequesSettingsController::class, 'storeCategory'])->name('settings-panel.cheques-settings.store-category');
    Route::put('cheques-settings/categories/{id}', [App\Http\Controllers\ChequesSettingsController::class, 'updateCategory'])->name('settings-panel.cheques-settings.update-category');
    Route::delete('cheques-settings/categories/{id}', [App\Http\Controllers\ChequesSettingsController::class, 'destroyCategory'])->name('settings-panel.cheques-settings.destroy-category');
    Route::post('cheques-settings/categories/reorder', [App\Http\Controllers\ChequesSettingsController::class, 'reorderCategories'])->name('settings-panel.cheques-settings.reorder-categories');
    Route::get('cheques-settings/fields/table-body', [App\Http\Controllers\ChequesSettingsController::class, 'tableBody'])->name('settings-panel.cheques-settings.table-body');
    Route::get('cheques-settings/fields/table-body/{categoryId}', [App\Http\Controllers\ChequesSettingsController::class, 'tableBodyCategory'])->name('settings-panel.cheques-settings.table-body-category');
    Route::get('cheques-settings/fields/config-schema/{dataType}', [App\Http\Controllers\ChequesSettingsController::class, 'fieldConfigSchema'])->name('settings-panel.cheques-settings.field-config-schema');
    Route::post('cheques-settings/fields', [App\Http\Controllers\ChequesSettingsController::class, 'storeField'])->name('settings-panel.cheques-settings.store-field');
    Route::post('cheques-settings/fields/{id}/assign-category', [App\Http\Controllers\ChequesSettingsController::class, 'assignCustomFieldCategory'])->name('settings-panel.cheques-settings.assign-custom-field-category');
    Route::put('cheques-settings/fields/{id}', [App\Http\Controllers\ChequesSettingsController::class, 'updateField'])->name('settings-panel.cheques-settings.update-field');
    Route::delete('cheques-settings/fields/{id}', [App\Http\Controllers\ChequesSettingsController::class, 'destroyField'])->name('settings-panel.cheques-settings.destroy-field');
    Route::post('cheques-settings/fields/{id}/flags', [App\Http\Controllers\ChequesSettingsController::class, 'updateCustomFieldFlags'])->name('settings-panel.cheques-settings.update-custom-field-flags');
    Route::post('cheques-settings/fields/reorder', [App\Http\Controllers\ChequesSettingsController::class, 'reorderFields'])->name('settings-panel.cheques-settings.reorder-fields');
    Route::get('cheques-settings/documents/table-body', [App\Http\Controllers\ChequesSettingsController::class, 'documentTypesTableBody'])->name('settings-panel.cheques-settings.document-types-table-body');
    Route::post('cheques-settings/documents', [App\Http\Controllers\ChequesSettingsController::class, 'storeDocumentType'])->name('settings-panel.cheques-settings.store-document-type');
    Route::put('cheques-settings/documents/{id}', [App\Http\Controllers\ChequesSettingsController::class, 'updateDocumentType'])->name('settings-panel.cheques-settings.update-document-type');
    Route::delete('cheques-settings/documents/{id}', [App\Http\Controllers\ChequesSettingsController::class, 'destroyDocumentType'])->name('settings-panel.cheques-settings.destroy-document-type');
    Route::post('cheques-settings/documents/reorder', [App\Http\Controllers\ChequesSettingsController::class, 'reorderDocumentTypes'])->name('settings-panel.cheques-settings.reorder-document-types');
    Route::get('cheques-settings/cheque-top/accordion-body', [App\Http\Controllers\ChequesSettingsController::class, 'chequeTopAccordionBody'])->name('settings-panel.cheques-settings.cheque-top-accordion-body');
    Route::post('cheques-settings/cheque-top/categories', [App\Http\Controllers\ChequesSettingsController::class, 'storeChequeTopCategory'])->name('settings-panel.cheques-settings.store-cheque-top-category');
    Route::get('cheques-settings/cheque-top/categories/{id}/field-values', [App\Http\Controllers\ChequesSettingsController::class, 'chequeTopCategoryFieldValues'])->name('settings-panel.cheques-settings.cheque-top-category-field-values');
    Route::put('cheques-settings/cheque-top/categories/{id}', [App\Http\Controllers\ChequesSettingsController::class, 'updateChequeTopCategory'])->name('settings-panel.cheques-settings.update-cheque-top-category');
    Route::delete('cheques-settings/cheque-top/categories/{id}', [App\Http\Controllers\ChequesSettingsController::class, 'destroyChequeTopCategory'])->name('settings-panel.cheques-settings.destroy-cheque-top-category');
    Route::post('cheques-settings/cheque-top/categories/{id}/visibility', [App\Http\Controllers\ChequesSettingsController::class, 'updateChequeTopCategoryVisibility'])->name('settings-panel.cheques-settings.update-cheque-top-category-visibility');
    Route::post('cheques-settings/cheque-top/options', [App\Http\Controllers\ChequesSettingsController::class, 'storeChequeTopOption'])->name('settings-panel.cheques-settings.store-cheque-top-option');
    Route::put('cheques-settings/cheque-top/options/{id}', [App\Http\Controllers\ChequesSettingsController::class, 'updateChequeTopOption'])->name('settings-panel.cheques-settings.update-cheque-top-option');
    Route::delete('cheques-settings/cheque-top/options/{id}', [App\Http\Controllers\ChequesSettingsController::class, 'destroyChequeTopOption'])->name('settings-panel.cheques-settings.destroy-cheque-top-option');
    Route::get('module-settings/cheques', function (\Illuminate\Http\Request $request) {
        $companySlug = (string) ($request->route('company_slug') ?? '');
        return redirect()->route(
            'settings-panel.cheques-settings.index',
            array_merge(['company_slug' => $companySlug], $request->query())
        );
    })->name('settings-panel.module-settings.cheques-alias');

    // Employee Settings (categories, fixed employee fields + employee custom fields)
    Route::get('employee-settings', [App\Http\Controllers\EmployeeSettingsController::class, 'index'])->name('settings-panel.employee-settings.index');
    Route::post('employee-settings/module-label', [App\Http\Controllers\EmployeeSettingsController::class, 'storeModuleLabel'])->name('settings-panel.employee-settings.store-module-label');
    Route::post('employee-settings/module-icon', [App\Http\Controllers\EmployeeSettingsController::class, 'storeModuleIcon'])->name('settings-panel.employee-settings.store-module-icon');
    Route::post('employee-settings/field-assignment', [App\Http\Controllers\EmployeeSettingsController::class, 'updateFieldAssignment'])->name('settings-panel.employee-settings.update-field-assignment');
    Route::post('employee-settings/field-assignment/display-label', [App\Http\Controllers\EmployeeSettingsController::class, 'updateFieldAssignmentLabel'])->name('settings-panel.employee-settings.update-field-assignment-label');
    Route::post('employee-settings/field-assignment/visibility', [App\Http\Controllers\EmployeeSettingsController::class, 'updateFieldAssignmentVisibility'])->name('settings-panel.employee-settings.update-field-assignment-visibility');
    Route::post('employee-settings/field-assignment/required', [App\Http\Controllers\EmployeeSettingsController::class, 'updateFieldAssignmentRequired'])->name('settings-panel.employee-settings.update-field-assignment-required');
    Route::post('employee-settings/field-assignments/reorder', [App\Http\Controllers\EmployeeSettingsController::class, 'reorderFieldAssignments'])->name('settings-panel.employee-settings.reorder-field-assignments');
    Route::get('employee-settings/categories/table-body', [App\Http\Controllers\EmployeeSettingsController::class, 'categoriesTableBody'])->name('settings-panel.employee-settings.categories-table-body');
    Route::post('employee-settings/categories', [App\Http\Controllers\EmployeeSettingsController::class, 'storeCategory'])->name('settings-panel.employee-settings.store-category');
    Route::put('employee-settings/categories/{id}', [App\Http\Controllers\EmployeeSettingsController::class, 'updateCategory'])->name('settings-panel.employee-settings.update-category');
    Route::delete('employee-settings/categories/{id}', [App\Http\Controllers\EmployeeSettingsController::class, 'destroyCategory'])->name('settings-panel.employee-settings.destroy-category');
    Route::post('employee-settings/categories/reorder', [App\Http\Controllers\EmployeeSettingsController::class, 'reorderCategories'])->name('settings-panel.employee-settings.reorder-categories');
    Route::get('employee-settings/fields/table-body', [App\Http\Controllers\EmployeeSettingsController::class, 'tableBody'])->name('settings-panel.employee-settings.table-body');
    Route::get('employee-settings/fields/table-body/{categoryId}', [App\Http\Controllers\EmployeeSettingsController::class, 'tableBodyCategory'])->name('settings-panel.employee-settings.table-body-category');
    Route::get('employee-settings/fields/config-schema/{dataType}', [App\Http\Controllers\EmployeeSettingsController::class, 'fieldConfigSchema'])->name('settings-panel.employee-settings.field-config-schema');
    Route::post('employee-settings/fields', [App\Http\Controllers\EmployeeSettingsController::class, 'storeField'])->name('settings-panel.employee-settings.store-field');
    Route::post('employee-settings/fields/{id}/assign-category', [App\Http\Controllers\EmployeeSettingsController::class, 'assignCustomFieldCategory'])->name('settings-panel.employee-settings.assign-custom-field-category');
    Route::put('employee-settings/fields/{id}', [App\Http\Controllers\EmployeeSettingsController::class, 'updateField'])->name('settings-panel.employee-settings.update-field');
    Route::delete('employee-settings/fields/{id}', [App\Http\Controllers\EmployeeSettingsController::class, 'destroyField'])->name('settings-panel.employee-settings.destroy-field');
    Route::post('employee-settings/fields/{id}/flags', [App\Http\Controllers\EmployeeSettingsController::class, 'updateCustomFieldFlags'])->name('settings-panel.employee-settings.update-custom-field-flags');
    Route::post('employee-settings/fields/reorder', [App\Http\Controllers\EmployeeSettingsController::class, 'reorderFields'])->name('settings-panel.employee-settings.reorder-fields');
    Route::get('employee-settings/documents/table-body', [App\Http\Controllers\EmployeeSettingsController::class, 'documentTypesTableBody'])->name('settings-panel.employee-settings.document-types-table-body');
    Route::post('employee-settings/documents', [App\Http\Controllers\EmployeeSettingsController::class, 'storeDocumentType'])->name('settings-panel.employee-settings.store-document-type');
    Route::put('employee-settings/documents/{id}', [App\Http\Controllers\EmployeeSettingsController::class, 'updateDocumentType'])->name('settings-panel.employee-settings.update-document-type');
    Route::delete('employee-settings/documents/{id}', [App\Http\Controllers\EmployeeSettingsController::class, 'destroyDocumentType'])->name('settings-panel.employee-settings.destroy-document-type');
    Route::post('employee-settings/documents/reorder', [App\Http\Controllers\EmployeeSettingsController::class, 'reorderDocumentTypes'])->name('settings-panel.employee-settings.reorder-document-types');
    Route::get('employee-settings/employee-top/accordion-body', [App\Http\Controllers\EmployeeSettingsController::class, 'employeeTopAccordionBody'])->name('settings-panel.employee-settings.employee-top-accordion-body');
    Route::post('employee-settings/employee-top/categories', [App\Http\Controllers\EmployeeSettingsController::class, 'storeEmployeeTopCategory'])->name('settings-panel.employee-settings.store-employee-top-category');
    Route::get('employee-settings/employee-top/categories/{id}/field-values', [App\Http\Controllers\EmployeeSettingsController::class, 'employeeTopCategoryFieldValues'])->name('settings-panel.employee-settings.employee-top-category-field-values');
    Route::put('employee-settings/employee-top/categories/{id}', [App\Http\Controllers\EmployeeSettingsController::class, 'updateEmployeeTopCategory'])->name('settings-panel.employee-settings.update-employee-top-category');
    Route::delete('employee-settings/employee-top/categories/{id}', [App\Http\Controllers\EmployeeSettingsController::class, 'destroyEmployeeTopCategory'])->name('settings-panel.employee-settings.destroy-employee-top-category');
    Route::post('employee-settings/employee-top/categories/{id}/visibility', [App\Http\Controllers\EmployeeSettingsController::class, 'updateEmployeeTopCategoryVisibility'])->name('settings-panel.employee-settings.update-employee-top-category-visibility');
    Route::post('employee-settings/employee-top/options', [App\Http\Controllers\EmployeeSettingsController::class, 'storeEmployeeTopOption'])->name('settings-panel.employee-settings.store-employee-top-option');
    Route::put('employee-settings/employee-top/options/{id}', [App\Http\Controllers\EmployeeSettingsController::class, 'updateEmployeeTopOption'])->name('settings-panel.employee-settings.update-employee-top-option');
    Route::delete('employee-settings/employee-top/options/{id}', [App\Http\Controllers\EmployeeSettingsController::class, 'destroyEmployeeTopOption'])->name('settings-panel.employee-settings.destroy-employee-top-option');
    Route::post('employee-settings/statuses', [App\Http\Controllers\EmployeeSettingsController::class, 'storeEmployeeStatus'])->name('settings-panel.employee-settings.store-employee-status');
    Route::put('employee-settings/statuses/{id}', [App\Http\Controllers\EmployeeSettingsController::class, 'updateEmployeeStatus'])->name('settings-panel.employee-settings.update-employee-status');
    Route::delete('employee-settings/statuses/{id}', [App\Http\Controllers\EmployeeSettingsController::class, 'destroyEmployeeStatus'])->name('settings-panel.employee-settings.destroy-employee-status');
    Route::get('module-settings/employee-settings', function (\Illuminate\Http\Request $request) {
        $companySlug = (string) ($request->route('company_slug') ?? '');
        return redirect()->route(
            'settings-panel.employee-settings.index',
            array_merge(['company_slug' => $companySlug], $request->query())
        );
    })->name('settings-panel.module-settings.employee-settings-alias');

    // Bike Settings: mount under module-settings/bike_list
    // (So the sidebar route `settings-panel/module-settings/bike_list` opens bike settings.)
    Route::post('module-settings/bike_list/module-label', [App\Http\Controllers\BikeSettingsController::class, 'storeModuleLabel'])->name('settings-panel.bike-settings.store-module-label');
    Route::post('module-settings/bike_list/module-icon', [App\Http\Controllers\BikeSettingsController::class, 'storeModuleIcon'])->name('settings-panel.bike-settings.store-module-icon');

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
    Route::post('module-settings/bike_list/fields/{id}/flags', [App\Http\Controllers\BikeSettingsController::class, 'updateCustomFieldFlags'])->name('settings-panel.bike-settings.update-custom-field-flags');
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

    // SIM assign-field settings (assign / return modals)
    Route::post('module-settings/sims/assign-fields', [App\Http\Controllers\SimSettingsController::class, 'updateAssignFieldAssignment'])->name('settings-panel.sim-settings.update-assign-field');
    Route::put('module-settings/sims/assign-fields/{id}', [App\Http\Controllers\SimSettingsController::class, 'updateAssignField'])->name('settings-panel.sim-settings.update-assign-field-item');
    Route::post('module-settings/sims/assign-fields/reorder', [App\Http\Controllers\SimSettingsController::class, 'reorderAssignFieldAssignments'])->name('settings-panel.sim-settings.reorder-assign-fields');
    Route::post('module-settings/sims/assign-fields/store', [App\Http\Controllers\SimSettingsController::class, 'storeAssignField'])->name('settings-panel.sim-settings.store-assign-field');
    Route::delete('module-settings/sims/assign-fields/{id}', [App\Http\Controllers\SimSettingsController::class, 'destroyAssignField'])->name('settings-panel.sim-settings.destroy-assign-field');

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
    Route::post('module-settings/{module}/legal-case-top', [App\Http\Controllers\ModuleSettingsController::class, 'updateLegalCaseTop'])->name('settings-panel.module-settings.update-legal-case-top')->where('module', '[A-Za-z0-9_-]+');
    Route::resource('legal-case-statuses', App\Http\Controllers\LegalCaseStatusController::class)->names('settings-panel.legal-case-statuses');
    Route::post('legal-case-statuses/reorder', [App\Http\Controllers\LegalCaseStatusController::class, 'reorder'])->name('settings-panel.legal-case-statuses.reorder');
    Route::get('legal-case-statuses/{id}/toggle-active', [App\Http\Controllers\LegalCaseStatusController::class, 'toggleActive'])->name('settings-panel.legal-case-statuses.toggle-active');
    Route::post('module-settings/{module}/bike-registration-top', [App\Http\Controllers\ModuleSettingsController::class, 'updateBikeRegistrationTop'])->name('settings-panel.module-settings.update-bike-registration-top')->where('module', '[A-Za-z0-9_-]+');

    // Centralized top bar settings (generic module storage)
    Route::prefix('module-settings/{module}/top-bar')->where(['module' => '[A-Za-z0-9_-]+'])->name('settings-panel.module-top-bar.')->group(function () {
        Route::get('accordion', [App\Http\Controllers\ModuleTopBarSettingsController::class, 'accordionBody'])->name('accordion');
        Route::post('categories', [App\Http\Controllers\ModuleTopBarSettingsController::class, 'storeCategory'])->name('store-category');
        Route::put('categories/{id}', [App\Http\Controllers\ModuleTopBarSettingsController::class, 'updateCategory'])->name('update-category');
        Route::delete('categories/{id}', [App\Http\Controllers\ModuleTopBarSettingsController::class, 'destroyCategory'])->name('destroy-category');
        Route::post('categories/{id}/visibility', [App\Http\Controllers\ModuleTopBarSettingsController::class, 'updateCategoryVisibility'])->name('update-visibility');
        Route::get('categories/{id}/field-values', [App\Http\Controllers\ModuleTopBarSettingsController::class, 'categoryFieldValues'])->name('field-values');
        Route::post('options', [App\Http\Controllers\ModuleTopBarSettingsController::class, 'storeOption'])->name('store-option');
        Route::put('options/{id}', [App\Http\Controllers\ModuleTopBarSettingsController::class, 'updateOption'])->name('update-option');
        Route::delete('options/{id}', [App\Http\Controllers\ModuleTopBarSettingsController::class, 'destroyOption'])->name('destroy-option');
        Route::post('categories/reorder', [App\Http\Controllers\ModuleTopBarSettingsController::class, 'reorderCategories'])->name('reorder-categories');
    });

    // Agreements (Settings: create + assign modules only)
    Route::prefix('agreements')->name('settings-panel.agreements.')->group(function () {
        Route::get('/', [App\Http\Controllers\AgreementSettingsController::class, 'index'])->name('index');
        Route::get('/create', [App\Http\Controllers\AgreementSettingsController::class, 'createAgreement'])->name('create-agreement');
        Route::post('/store', [App\Http\Controllers\AgreementSettingsController::class, 'storeAgreement'])->name('store-agreement');
        Route::get('/categories/{category}', [App\Http\Controllers\AgreementSettingsController::class, 'showAgreement'])->name('show-agreement')->whereNumber('category');
        Route::get('/categories/{category}/edit', [App\Http\Controllers\AgreementSettingsController::class, 'editAgreement'])->name('edit-agreement')->whereNumber('category');
        Route::put('/categories/{category}', [App\Http\Controllers\AgreementSettingsController::class, 'updateAgreement'])->name('update-agreement')->whereNumber('category');
        Route::delete('/categories/{category}', [App\Http\Controllers\AgreementSettingsController::class, 'destroyAgreement'])->name('destroy-agreement')->whereNumber('category');
        Route::post('/categories/{category}/toggle-status', [App\Http\Controllers\AgreementSettingsController::class, 'toggleAgreementStatus'])->name('toggle-agreement-status')->whereNumber('category');
    });

    // Module settings page + label update
    Route::post('module-settings/dashboard/cards', [App\Http\Controllers\DashboardSettingsController::class, 'update'])->name('settings-panel.dashboard-settings.cards');
    Route::get('module-settings/{module}', [App\Http\Controllers\ModuleSettingsController::class, 'index'])->name('settings-panel.module-settings.index')->where('module', '[A-Za-z0-9_-]+');
    Route::post('module-settings/{module}/module-label', [App\Http\Controllers\ModuleSettingsController::class, 'storeModuleLabel'])->name('settings-panel.module-settings.store-module-label')->where('module', '[A-Za-z0-9_-]+');
    Route::post('module-settings/{module}/module-icon', [App\Http\Controllers\ModuleSettingsController::class, 'storeModuleIcon'])->name('settings-panel.module-settings.store-module-icon')->where('module', '[A-Za-z0-9_-]+');
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
