<?php

use App\Modules\VehicleBrandMaster\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'can:vehicle_brand_master.view'])->group(function () {
    Route::get('/vehicle-brand-master', Index::class)->name('vehicle-brand-master.index');
});
