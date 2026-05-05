<?php

use App\Modules\VehicleVariantMaster\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/vehicle-variant-master', Index::class)->name('vehicle-variant-master.index');
});
