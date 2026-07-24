<?php

use App\Modules\HsnMaster\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'can:hsn_master.view'])->group(function () {
    Route::get('/hsn-master', Index::class)->name('hsn-master.index');
});
