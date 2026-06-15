<?php

use App\Modules\RequestedRepairMaster\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'can:requested_repair_master.view'])->group(function () {
    Route::get('/requested-repair-master', Index::class)->name('requested-repair-master.index');
});
