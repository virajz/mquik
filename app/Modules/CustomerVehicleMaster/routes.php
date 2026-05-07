<?php

use App\Modules\CustomerVehicleMaster\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'can:customer_vehicle_master.view'])->group(function () {
    Route::get('/customer-vehicle-master', Index::class)->name('customer-vehicle-master.index');
});
