<?php

/**
 * Company-scoped route subset (reference only). The live tenant routes are registered in routes/web.php
 * under Route::prefix('app/{company_slug}') — this file is not required from web.php by default.
 */

use App\Http\Controllers\HomeController;
use App\Http\Controllers\ItemsController;
use App\Http\Controllers\BikesController;
use App\Http\Controllers\CustomersController;
use App\Http\Controllers\UserController;

Route::resource('items', ItemsController::class);
Route::resource('garage-items', App\Http\Controllers\GarageItemsController::class);
Route::get('garage-items/{id}/vouchers', [App\Http\Controllers\GarageItemsController::class, 'vouchers'])->name('garage-items.vouchers');

Route::any('user/profile', [UserController::class, 'profile'])->name('profile');
Route::any('user/services/{id}', [UserController::class, 'services'])->name('user_services');

Route::get('bikes/import', [BikesController::class, 'importbikes'])->name('bikes.import');
Route::post('bikes/import', [BikesController::class, 'processImport'])->name('bikes.processImport');
Route::resource('bikes', BikesController::class);

Route::resource('customers', App\Http\Controllers\CustomersController::class)->parameters(['customers' => 'id']);
Route::get('customer/ledger/{id}', [App\Http\Controllers\CustomersController::class, 'ledger'])->name('customer.ledger');

// Add more routes from web.php auth group as needed (employees, riders, vouchers, etc.)
