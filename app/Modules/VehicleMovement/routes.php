<?php

use App\Modules\VehicleMovement\Livewire\Edit;
use App\Modules\VehicleMovement\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/vehicle-movement', Index::class)
        ->middleware('can:vehicle_movement.view')->name('vehicle-movement.index');
    Route::get('/vehicle-movement/create', Edit::class)
        ->middleware('can:vehicle_movement.create')->name('vehicle-movement.create');
    Route::get('/vehicle-movement/{vehicleMovement}/edit', Edit::class)
        ->middleware('can:vehicle_movement.update')->name('vehicle-movement.edit');
});
