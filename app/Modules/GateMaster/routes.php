<?php

use App\Modules\GateMaster\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'can:gate_master.view'])->group(function () {
    Route::get('/gate-master', Index::class)->name('gate-master.index');
});
