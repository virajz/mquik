<?php

use App\Modules\VehicleColorMaster\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/vehicle-color-master', Index::class)->name('vehicle-color-master.index');
});
