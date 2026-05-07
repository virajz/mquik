<?php

use App\Modules\LocationMaster\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'can:location_master.view'])->group(function () {
    Route::get('/location-master', Index::class)->name('location-master.index');
});
