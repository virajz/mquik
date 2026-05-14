<?php

use App\Modules\SpareMaster\Livewire\Edit;
use App\Modules\SpareMaster\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/spare-master', Index::class)
        ->middleware('can:spare_master.view')
        ->name('spare-master.index');

    Route::get('/spare-master/create', Edit::class)
        ->middleware('can:spare_master.create')
        ->name('spare-master.create');

    Route::get('/spare-master/{spare}/edit', Edit::class)
        ->middleware('can:spare_master.update')
        ->name('spare-master.edit');
});
