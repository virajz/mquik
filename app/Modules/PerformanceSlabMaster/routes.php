<?php

use App\Modules\PerformanceSlabMaster\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'can:performance_slab_master.view'])->group(function () {
    Route::get('/performance-slab-master', Index::class)->name('performance-slab-master.index');
});
