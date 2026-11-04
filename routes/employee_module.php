<?php

/**
 * Employees overall module routes (employees + attendance + employee invoices).
 * Included inside the app/{company_slug} tenant group in routes/web.php.
 * Keep this file in sync on deploy with employee Blade views to avoid RouteNotFoundException.
 */

use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\EmployeeInvoicesController;

// --- Employees ---
Route::get('employees/payments', [EmployeeController::class, 'payment'])->name('employee.payment');
Route::get('employees/advanceloan/{id}', [EmployeeController::class, 'advanceloan'])->name('employees.advanceloan');
Route::get('employees/penalty/{id}', [EmployeeController::class, 'penalty'])->name('employees.penalty');
Route::get('employees/incentive/{id}', [EmployeeController::class, 'incentive'])->name('employees.incentive');
Route::post('employees/storeadvanceloan', [EmployeeController::class, 'storeadvanceloan'])->name('employees.storeadvanceloan');
Route::post('employees/storepenalty', [EmployeeController::class, 'storepenalty'])->name('employees.storepenalty');
Route::post('employees/storeincentive', [EmployeeController::class, 'storeincentive'])->name('employees.storeincentive');
Route::post('employees/filter-ajax', [EmployeeController::class, 'filterAjax'])->name('employees.filterAjax');
Route::post('employees/toggle-favorite/{id}', [EmployeeController::class, 'toggleFavorite'])->name('employees.toggleFavorite');
Route::get('employees/invoices/{id}', [EmployeeController::class, 'invoices'])->name('employee.invoices');
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

// --- Attendance ---
Route::get('attendance/summary', [AttendanceController::class, 'summary'])->name('attendance.summary');
Route::get('attendance/export', [AttendanceController::class, 'export'])->name('attendance.export');
Route::get('attendance/summary/export', [AttendanceController::class, 'exportSummary'])->name('attendance.summary.export');
Route::match(['get', 'post'], 'attendance/import', [AttendanceController::class, 'import'])->name('attendance.import');
Route::get('attendance/import/template', [AttendanceController::class, 'importTemplate'])->name('attendance.import.template');
Route::post('attendance/upsert-mark', [AttendanceController::class, 'upsertMark'])->name('attendance.upsertMark');
Route::post('attendance/clear-mark', [AttendanceController::class, 'clearMark'])->name('attendance.clearMark');
Route::get('/attendance/create/{refType}', [AttendanceController::class, 'create'])->name('attendance.create');
Route::resource('attendance', AttendanceController::class)->except(['create']);
Route::post('attendance/bulk-mark', [AttendanceController::class, 'bulkMark'])->name('attendance.bulk-mark');
Route::get('attendance/users/{refType}', [AttendanceController::class, 'getUsers'])->name('attendance.users');

// --- Employee invoices (custom routes before resource) ---
Route::get('employeeInvoices/import', [EmployeeInvoicesController::class, 'importForm'])->name('employeeInvoices.import.form');
Route::post('employeeInvoices/import', [EmployeeInvoicesController::class, 'import'])->name('employeeInvoices.import');
Route::any('employeeInvoices/sendemail/{id}', [EmployeeInvoicesController::class, 'sendEmail'])->name('employeeInvoices.sendEmail');
Route::get('employeeInvoices/export', [EmployeeInvoicesController::class, 'export'])->name('employeeInvoices.export');
Route::any('employee/invoice-mark-paid/{id}', [EmployeeInvoicesController::class, 'markAsPaid'])->name('employeeInvoices.markAsPaid');
Route::any('employee/invoice-mark-settled/{id}', [EmployeeInvoicesController::class, 'markAsSettled'])->name('employeeInvoices.markAsSettled');
Route::get('employeeInvoices/{id}/download', [EmployeeInvoicesController::class, 'download'])->name('employeeInvoices.download');
Route::resource('employeeInvoices', EmployeeInvoicesController::class);
Route::get('employeeInvoices/delete/{id}', [EmployeeInvoicesController::class, 'destroy'])->name('employeeInvoices.delete');
Route::post('employeeInvoices/bulk-delete', [EmployeeInvoicesController::class, 'bulkDelete'])->name('employeeInvoices.bulkDelete');
