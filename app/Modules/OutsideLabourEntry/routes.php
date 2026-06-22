<?php

use App\Modules\OutsideLabourEntry\Livewire\Edit;
use App\Modules\OutsideLabourEntry\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/outside-labour-entries', Index::class)
        ->middleware('can:outside_labour_entry.view')
        ->name('outside-labour-entry.index');

    Route::get('/outside-labour-entries/create', Edit::class)
        ->middleware('can:outside_labour_entry.create')
        ->name('outside-labour-entry.create');

    Route::get('/outside-labour-entries/{outsideLabourEntry}/edit', Edit::class)
        ->middleware('can:outside_labour_entry.update')
        ->name('outside-labour-entry.edit');
});
