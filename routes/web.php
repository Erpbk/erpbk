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
// Main Page Route

// pages
Route::get('/pages/misc-error', [MiscError::class, 'index'])->name('pages-misc-error');

Route::middleware(['auth', 'web'])->group(function () {

    Route::get('/', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
    Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home-dashboard');

    // ============================================================
    // CENTRALIZED TRASH/RECYCLE BIN MODULE
    // ============================================================
    Route::get('/trash', [App\Http\Controllers\TrashController::class, 'index'])->name('trash.index');
    Route::post('/trash/{module}/{id}/restore', [App\Http\Controllers\TrashController::class, 'restore'])->name('trash.restore');
    Route::delete('/trash/{module}/{id}/force-destroy', [App\Http\Controllers\TrashController::class, 'forceDestroy'])->name('trash.force-destroy');
    Route::get('/trash/stats', [App\Http\Controllers\TrashController::class, 'stats'])->name('trash.stats');

    Route::resource('items', App\Http\Controllers\ItemsController::class);
    Route::resource('garage-items', App\Http\Controllers\GarageItemsController::class);
    Route::get('garage-items/{id}/vouchers', [App\Http\Controllers\GarageItemsController::class, 'vouchers'])->name('garage-items.vouchers');

    Route::resource('users', App\Http\Controllers\UserController::class);
    Route::any('/user/profile', [App\Http\Controllers\UserController::class, 'profile'])->name('profile');
    Route::any('/user/services/{id}', [App\Http\Controllers\UserController::class, 'services'])->name('user_services');
    Route::resource('permissions', App\Http\Controllers\PermissionsController::class);
    Route::resource('roles', App\Http\Controllers\RolesController::class);


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

    Route::resource('bikes', App\Http\Controllers\BikesController::class);



    Route::get('bikes/import-bikes', [\App\Http\Controllers\BikesController::class, 'importbikes'])->name('bikes.importbikes');
    Route::post('bikes/process-import', [\App\Http\Controllers\BikesController::class, 'processImport'])->name('bikes.processImport');


    Route::resource('customers', App\Http\Controllers\CustomersController::class);
    Route::get('customer/ledger/{id}', [\App\Http\Controllers\CustomersController::class, 'ledger'])->name('customer.ledger');
    Route::get('customer/files/{id}', [\App\Http\Controllers\CustomersController::class, 'files'])->name('customer.files');
    Route::get('customers/delete/{id}', [\App\Http\Controllers\CustomersController::class, 'destroy'])->name('customers.delete');
    // Customers Trash Routes
    Route::get('customers/trash', [\App\Http\Controllers\CustomersController::class, 'trash'])->name('customers.trash');
    Route::post('customers/trash/{id}/restore', [\App\Http\Controllers\CustomersController::class, 'restoreTrash'])->name('customers.restore');
    Route::delete('customers/trash/{id}/force-destroy', [\App\Http\Controllers\CustomersController::class, 'forceDestroyTrash'])->name('customers.force-destroy');

    Route::get('rtaFines/import/{id}', [\App\Http\Controllers\RtaFinesController::class, 'importForm'])->name('rtaFines.import.form');
    Route::post('rtaFines/import', [\App\Http\Controllers\RtaFinesController::class, 'import'])->name('rtaFines.import');

    Route::resource('rtaFines', App\Http\Controllers\RtaFinesController::class);
    Route::post('rtaFines/store', [\App\Http\Controllers\RtaFinesController::class, 'store'])->name('rtaFines.store');
    Route::get('rtaFines/edit/{id}', [\App\Http\Controllers\RtaFinesController::class, 'edit'])->name('rtaFines.edit');
    Route::post('rtaFines/update', [\App\Http\Controllers\RtaFinesController::class, 'update'])->name('rtaFines.update');
    Route::get('rtaFines/create/{id}', [\App\Http\Controllers\RtaFinesController::class, 'create'])->name('rtaFines.create');
    Route::any('rtaFines/attach_file/{id}', [\App\Http\Controllers\RtaFinesController::class, 'fileUpload'])->name('rtaFines.fileupload');
    Route::get('rtaFines/delete/{id}', [\App\Http\Controllers\RtaFinesController::class, 'destroy'])->name('rtaFines.delete');

    Route::post('rtaFines/accountcreate', [\App\Http\Controllers\RtaFinesController::class, 'accountcreate'])->name('rtaFines.accountcreate');
    Route::post('rtaFines/editaccount', [\App\Http\Controllers\RtaFinesController::class, 'editaccount'])->name('rtaFines.editaccount');
    Route::get('rtaFines/deleteaccount/{id}', [\App\Http\Controllers\RtaFinesController::class, 'deleteaccount'])->name('rtaFines.deleteaccount');
    Route::get('rtaFines/tickets/{id}', [\App\Http\Controllers\RtaFinesController::class, 'tickets'])->name('rtaFines.tickets');
    Route::post('rtaFines/payfine', [\App\Http\Controllers\RtaFinesController::class, 'payfine'])->name('rtaFines.payfine');
    Route::get('rtaFines/viewvoucher/{id}', [\App\Http\Controllers\RtaFinesController::class, 'viewvoucher'])->name('rtaFines.viewvoucher');
    Route::get('rtaFines/getrider/{id}', [\App\Http\Controllers\RtaFinesController::class, 'getrider']);






    Route::resource('VisaExpense', App\Http\Controllers\VisaexpenseController::class);

    // Visa Status Management Routes
    Route::resource('visa-statuses', App\Http\Controllers\VisaStatusController::class);
    Route::get('visa-statuses/{id}/toggle-active', [App\Http\Controllers\VisaStatusController::class, 'toggleActive'])->name('visa-statuses.toggle-active');
    Route::post('VisaExpense/store', [\App\Http\Controllers\VisaexpenseController::class, 'store'])->name('VisaExpense.store');
    Route::get('VisaExpense/edit/{id}', [\App\Http\Controllers\VisaexpenseController::class, 'edit'])->name('VisaExpense.edit');
    Route::post('VisaExpense/update', [\App\Http\Controllers\VisaexpenseController::class, 'update'])->name('VisaExpense.update');
    Route::get('VisaExpense/create/{id}', [\App\Http\Controllers\VisaexpenseController::class, 'create'])->name('VisaExpense.create');
    Route::any('VisaExpense/attach_file/{id}', [\App\Http\Controllers\VisaexpenseController::class, 'fileUpload'])->name('VisaExpense.fileupload');
    Route::get('VisaExpense/delete/{id}', [\App\Http\Controllers\VisaexpenseController::class, 'destroy'])->name('VisaExpense.delete');
    Route::get('VisaExpense/installmentPlan/{id}', [\App\Http\Controllers\VisaexpenseController::class, 'installmentPlan'])->name('VisaExpense.installmentPlan');

    // Simple Installment Plan Routes
    Route::get('VisaExpense/createInstallmentPlanForm/{riderId}', [\App\Http\Controllers\VisaexpenseController::class, 'createInstallmentPlanForm'])->name('VisaExpense.createInstallmentPlanForm');
    Route::post('VisaExpense/createInstallmentPlan', [\App\Http\Controllers\VisaexpenseController::class, 'createInstallmentPlan'])->name('VisaExpense.createInstallmentPlan');
    Route::post('VisaExpense/payInstallment', [\App\Http\Controllers\VisaexpenseController::class, 'payInstallment'])->name('VisaExpense.payInstallment');
    Route::post('VisaExpense/updateInstallmentField', [\App\Http\Controllers\VisaexpenseController::class, 'updateInstallmentField'])->name('VisaExpense.updateInstallmentField');
    Route::post('VisaExpense/finalizePayment', [\App\Http\Controllers\VisaexpenseController::class, 'finalizePayment'])->name('VisaExpense.finalizePayment');
    Route::get('VisaExpense/deleteInstallment/{id}', [\App\Http\Controllers\VisaexpenseController::class, 'deleteInstallment'])->name('VisaExpense.deleteInstallment');
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

    /* Rider section starts from here */

    Route::resource('riders', App\Http\Controllers\RidersController::class);
    Route::post('riders/filter-ajax', [\App\Http\Controllers\RidersController::class, 'filterAjax'])->name('riders.filterAjax');
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
    Route::post('riders/toggle-absconder/{id}', [\App\Http\Controllers\RidersController::class, 'toggleAbsconder'])->name('riders.toggleAbsconder');
    Route::post('riders/toggle-flowup/{id}', [\App\Http\Controllers\RidersController::class, 'toggleFlowup'])->name('riders.toggleFlowup');
    Route::post('riders/toggle-llicense/{id}', [\App\Http\Controllers\RidersController::class, 'toggleLlicense'])->name('riders.toggleLlicense');
    Route::post('riders/toggle-walker/{id}', [\App\Http\Controllers\RidersController::class, 'toggleWalker'])->name('riders.toggleWalker');
    Route::post('riders/toggle-mol/{id}', [\App\Http\Controllers\RidersController::class, 'toggleMol'])->name('riders.toggleMol');
    Route::post('riders/toggle-pro/{id}', [\App\Http\Controllers\RidersController::class, 'togglePro'])->name('riders.togglePro');
    Route::post('riders/return-bike/{id}', [\App\Http\Controllers\RidersController::class, 'returnBike'])->name('riders.returnBike');
    Route::post('riders/add-recruiter', [\App\Http\Controllers\RidersController::class, 'addRecruiter'])->name('riders.addRecruiter');
    Route::get('riders/vendorcharges/{id}', [\App\Http\Controllers\RidersController::class, 'vendorcharges'])->name('riders.vendorcharges');
    Route::post('riders/storevendorcharges', [\App\Http\Controllers\RidersController::class, 'storevendorcharges'])->name('riders.storevendorcharges');



    Route::resource('riderleads', App\Http\Controllers\riderhiringController::class);














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
    Route::get('items/delete/{id}', [\App\Http\Controllers\ItemsController::class, 'destroy'])->name('items.delete');

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

    Route::resource('leasingCompanies', App\Http\Controllers\LeasingCompaniesController::class);
    Route::get('leasingCompanies/delete/{id}', [\App\Http\Controllers\LeasingCompaniesController::class, 'destroy'])->name('leasingCompanies.delete');
    // Leasing Companies Trash Routes
    Route::get('leasingCompanies/trash', [\App\Http\Controllers\LeasingCompaniesController::class, 'trash'])->name('leasingCompanies.trash');
    Route::post('leasingCompanies/trash/{id}/restore', [\App\Http\Controllers\LeasingCompaniesController::class, 'restoreTrash'])->name('leasingCompanies.restore');
    Route::delete('leasingCompanies/trash/{id}/force-destroy', [\App\Http\Controllers\LeasingCompaniesController::class, 'forceDestroyTrash'])->name('leasingCompanies.force-destroy');
    Route::resource('garages', App\Http\Controllers\GaragesController::class);
    Route::get('garages/delete/{id}', [\App\Http\Controllers\GaragesController::class, 'destroy'])->name('garages.delete');
    Route::resource('banks', App\Http\Controllers\BanksController::class);
    Route::get('bank/ledger/{id}', [\App\Http\Controllers\BanksController::class, 'ledger'])->name('bank.ledger');
    Route::get('bank/files/{id}', [\App\Http\Controllers\BanksController::class, 'files'])->name('bank.files');
    Route::get('bank/delete/{id}', [\App\Http\Controllers\BanksController::class, 'destroy'])->name('bank.delete');
    Route::get('banks/receipts', [\App\Http\Controllers\BanksController::class, 'receipts'])->name('banks.receipts');
    Route::get('banks/payments', [\App\Http\Controllers\BanksController::class, 'payments'])->name('banks.payments');

    // Soft Delete Routes for Banks - DEPRECATED: Use centralized trash module (/trash)
    // Route::get('banks/trashed/list', [\App\Http\Controllers\BanksController::class, 'trashed'])->name('banks.trashed');
    // Route::post('banks/{id}/restore', [\App\Http\Controllers\BanksController::class, 'restore'])->name('banks.restore');
    // Route::delete('banks/{id}/force-delete', [\App\Http\Controllers\BanksController::class, 'forceDestroy'])->name('banks.force-destroy');

    Route::resource('vouchers', \App\Http\Controllers\VouchersController::class);
    Route::any('voucher/import', [\App\Http\Controllers\VouchersController::class, 'import'])->name('voucher.import');
    Route::get('get_invoice_balance', [\App\Http\Controllers\VouchersController::class, 'GetInvoiceBalance'])->name('get_invoice_balance');
    Route::get('fetch_invoices/{id}/{vt}', [\App\Http\Controllers\VouchersController::class, 'fetch_invoices']);
    /*   Route::any('attach_file/{id}', 'VouchersController@fileUpload'); */
    Route::any('voucher/attach_file/{id}', [\App\Http\Controllers\VouchersController::class, 'fileUpload'])->name('voucher.fileupload');


    Route::prefix('settings')->group(function () {

        Route::any('/company', [HomeController::class, 'settings'])->name('settings');
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

        Route::resource('accounts', App\Http\Controllers\AccountsController::class);
        Route::get('tree', [\App\Http\Controllers\AccountsController::class, 'tree'])->name('accounts.tree');
        // Accounts Trash Routes
        Route::get('trash', [\App\Http\Controllers\AccountsController::class, 'trash'])->name('accounts.trash');
        Route::post('trash/{id}/restore', [\App\Http\Controllers\AccountsController::class, 'restoreTrash'])->name('accounts.restore');
        Route::delete('trash/{id}/force-destroy', [\App\Http\Controllers\AccountsController::class, 'forceDestroyTrash'])->name('accounts.force-destroy');

        Route::get('/ledgerreport', [LedgerController::class, 'ledger'])->name('accounts.ledgerreport');
        Route::get('/ledger', [LedgerController::class, 'index'])->name('accounts.ledger');
        Route::get('/ledger/data', [LedgerController::class, 'getLedgerData'])->name('ledger.data');
        Route::get('/ledger/export', [LedgerController::class, 'export'])->name('ledger.export');
        Route::post('accounts/{id}/toggle-lock', [App\Http\Controllers\AccountsController::class, 'toggleLock'])->name('accounts.toggleLock');
    });

    // Activity Logs Routes
    Route::prefix('activity-logs')->group(function () {
        Route::get('/', [ActivityLogController::class, 'index'])->name('activity-logs.index');
        Route::get('/{activityLog}', [ActivityLogController::class, 'show'])->name('activity-logs.show');
        Route::get('/api/statistics', [ActivityLogController::class, 'statistics'])->name('activity-logs.statistics');
    });
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
Route::middleware(['auth'])->group(function () {
    // Suppliers
    Route::resource('suppliers', SupplierController::class);
    Route::get('/suppliers/show/{id}', [SupplierController::class, 'show'])->name('suppliers.show');
    Route::get('/suppliers/ledger/{id}', [SupplierController::class, 'ledger'])->name('suppliers.ledger');
    Route::get('/suppliers/{id}', [SupplierController::class, 'show'])->name('suppliers.show');
    Route::get('/suppliers/{id}/edit', [SupplierController::class, 'edit'])->name('suppliers.edit');
    Route::get('suppliers/delete/{id}', [\App\Http\Controllers\GaragesController::class, 'destroy'])->name('suppliers.delete');

    // Suppliers
    Route::resource('suppliers', SupplierController::class);
    Route::get('suppliers/datatable', [SupplierController::class, 'datatable'])->name('suppliers.datatable');
    Route::get('suppliers/document/{id}', [SupplierController::class, 'document'])->name('suppliers.document');
    Route::get('suppliers/files/{id}', [SupplierController::class, 'files'])->name('suppliers.files');
    // Suppliers Trash Routes
    Route::get('suppliers/trash', [SupplierController::class, 'trash'])->name('suppliers.trash');
    Route::post('suppliers/trash/{id}/restore', [SupplierController::class, 'restoreTrash'])->name('suppliers.restore');
    Route::delete('suppliers/trash/{id}/force-destroy', [SupplierController::class, 'forceDestroyTrash'])->name('suppliers.force-destroy');

    // Supplier invoices
    Route::resource('supplierInvoices', SupplierInvoicesController::class);
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
Route::middleware('auth')->group(function () {
    Route::resource('upload_files', UploadFilesController::class);
    Route::get('/upload_files', [UploadFilesController::class, 'index'])->name('upload_files.index');
    Route::get('/upload_files/create', [UploadFilesController::class, 'create'])->name('upload_files.create');
    Route::post('/upload_files', [UploadFilesController::class, 'store'])->name('upload_files.store');
    Route::get('/upload_files/{id}', [UploadFilesController::class, 'show'])->name('upload_files.show');
    Route::get('/upload_files/{id}/edit', [UploadFilesController::class, 'edit'])->name('upload_files.edit');
    Route::put('/upload_files/{id}', [UploadFilesController::class, 'update'])->name('upload_files.update');
    Route::delete('/upload_files/{id}', [UploadFilesController::class, 'destroy'])->name('upload_files.destroy');
});

Route::resource('payments', App\Http\Controllers\PaymentController::class);
Route::resource('receipts', App\Http\Controllers\ReceiptController::class);
Route::get('payments/byparent/{id}', [\App\Http\Controllers\PaymentController::class, 'byparent']);
Route::get('payments/headbytype/{id}', [\App\Http\Controllers\PaymentController::class, 'headbytype']);
Route::get('receipts/byparent/{id}', [\App\Http\Controllers\ReceiptController::class, 'byparent']);
Route::get('receipts/headbytype/{id}', [\App\Http\Controllers\ReceiptController::class, 'headbytype']);



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
