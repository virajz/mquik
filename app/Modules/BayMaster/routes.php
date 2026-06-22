<?php

use App\Modules\BayMaster\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'can:bay_master.view'])->group(function () {
    Route::get('/bay-master', Index::class)->name('bay-master.index');
});
