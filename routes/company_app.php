<?php

/**
 * Company-scoped app routes. Loaded inside Route::prefix('app/{company_id}') with tenant + auth.
 * Paths here are relative to /app/{company_id}/. Same controllers as main app; they use default DB (tenant).
 * Copy additional routes from the main auth group in web.php as needed.
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
