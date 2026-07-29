<?php

use App\Modules\VehicleAmc\Livewire\Edit;
use App\Modules\VehicleAmc\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/vehicle-amc', Index::class)
        ->middleware('can:vehicle_amc.view')->name('vehicle-amc.index');
    Route::get('/vehicle-amc/create', Edit::class)
        ->middleware('can:vehicle_amc.create')->name('vehicle-amc.create');
    Route::get('/vehicle-amc/{vehicleAmc}/edit', Edit::class)
        ->middleware('can:vehicle_amc.update')->name('vehicle-amc.edit');
});
