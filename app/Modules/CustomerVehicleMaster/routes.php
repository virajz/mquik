<?php

use App\Modules\CustomerVehicleMaster\Livewire\Edit;
use App\Modules\CustomerVehicleMaster\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/customer-vehicle-master', Index::class)
        ->middleware('can:customer_vehicle_master.view')
        ->name('customer-vehicle-master.index');

    Route::get('/customer-vehicle-master/create', Edit::class)
        ->middleware('can:customer_vehicle_master.create')
        ->name('customer-vehicle-master.create');

    Route::get('/customer-vehicle-master/{customer_vehicle}/edit', Edit::class)
        ->middleware('can:customer_vehicle_master.update')
        ->name('customer-vehicle-master.edit');
});
