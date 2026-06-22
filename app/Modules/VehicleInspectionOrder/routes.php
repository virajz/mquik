<?php

use App\Modules\VehicleInspectionOrder\Livewire\Edit;
use App\Modules\VehicleInspectionOrder\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/vehicle-inspection-orders', Index::class)
        ->middleware('can:vehicle_inspection_order.view')
        ->name('vehicle-inspection-order.index');

    Route::get('/vehicle-inspection-orders/create', Edit::class)
        ->middleware('can:vehicle_inspection_order.create')
        ->name('vehicle-inspection-order.create');

    Route::get('/vehicle-inspection-orders/{vehicleInspectionOrder}/edit', Edit::class)
        ->middleware('can:vehicle_inspection_order.update')
        ->name('vehicle-inspection-order.edit');
});
