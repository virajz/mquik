<?php

use App\Modules\ChallanEntry\Livewire\Edit;
use App\Modules\ChallanEntry\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/challan-entries', Index::class)
        ->middleware('can:challan_entry.view')
        ->name('challan-entry.index');

    Route::get('/challan-entries/create', Edit::class)
        ->middleware('can:challan_entry.create')
        ->name('challan-entry.create');

    Route::get('/challan-entries/{challan}/edit', Edit::class)
        ->middleware('can:challan_entry.update')
        ->name('challan-entry.edit');
});
