<?php

use App\Modules\VehicleModelMaster\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'can:vehicle_model_master.view'])->group(function () {
    Route::get('/vehicle-model-master', Index::class)->name('vehicle-model-master.index');
});
