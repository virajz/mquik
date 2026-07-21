<?php

use App\Modules\DistanceSlabMaster\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'can:distance_slab_master.view'])->group(function () {
    Route::get('/distance-slab-master', Index::class)->name('distance-slab-master.index');
});
