<?php

use App\Modules\VehicleModelMaster\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/vehicle-model-master', Index::class)->name('vehicle-model-master.index');
});
