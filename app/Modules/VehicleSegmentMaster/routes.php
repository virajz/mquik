<?php

use App\Modules\VehicleSegmentMaster\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'can:vehicle_segment_master.view'])->group(function () {
    Route::get('/vehicle-segment-master', Index::class)->name('vehicle-segment-master.index');
});
