<?php

use App\Modules\RegionMaster\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/region-master', Index::class)->name('region-master.index');
});
