<?php

use App\Modules\LabourMaster\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/labour-master', Index::class)
        ->middleware('can:labour_master.view')
        ->name('labour-master.index');
});
