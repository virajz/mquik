<?php

use App\Modules\TransportModeMaster\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'can:transport_mode_master.view'])->group(function () {
    Route::get('/transport-mode-master', Index::class)->name('transport-mode-master.index');
});
