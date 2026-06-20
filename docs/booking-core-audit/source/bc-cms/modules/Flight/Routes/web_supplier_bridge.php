<?php

use Illuminate\Support\Facades\Route;
use Modules\Flight\Controllers\SupplierCheckoutController;
use Modules\Flight\Controllers\SupplierWebhookController;
use Modules\Flight\Controllers\Admin\SupplierBookingAdminController;

// Add these routes to modules/Flight/Routes/web.php or load this file from the Flight RouteServiceProvider.
Route::group(['prefix' => config('flight.flight_route_prefix', 'flight'), 'as' => 'flight.'], function () {
    Route::post('/supplier/quote', [SupplierCheckoutController::class, 'quote'])->name('supplier.quote');
    Route::post('/supplier/webhooks/payment/{provider}', [SupplierWebhookController::class, 'payment'])->name('supplier.webhook.payment');
});

Route::group([
    'prefix' => 'admin/module/flight/supplier-bookings',
    'as' => 'flight.admin.supplier-bookings.',
    'middleware' => ['web', 'auth', 'dashboard'],
], function () {
    Route::get('/', [SupplierBookingAdminController::class, 'index'])->name('index');
    Route::get('/{id}', [SupplierBookingAdminController::class, 'detail'])->name('detail');
    Route::post('/{id}/retry-ticketing', [SupplierBookingAdminController::class, 'retryTicketing'])->name('retry');
    Route::post('/{id}/manual-review', [SupplierBookingAdminController::class, 'markManualReview'])->name('manual-review');
});
