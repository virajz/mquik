<?php

use App\Modules\JobHistory\Livewire\Show;
use App\Modules\JobHistory\Livewire\VehicleTimeline;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/job-cards/{jobCard}/history', Show::class)
        ->middleware('can:job_history.view')
        ->name('job-history.show');

    Route::get('/vehicles/{customerVehicle}/timeline', VehicleTimeline::class)
        ->middleware('can:job_history.view')
        ->name('job-history.vehicle-timeline');
});
