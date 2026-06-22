<?php

use App\Modules\StandardObservationMaster\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'can:standard_observation_master.view'])->group(function () {
    Route::get('/standard-observation-master', Index::class)->name('standard-observation-master.index');
});
