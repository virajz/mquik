<?php

use App\Modules\PickupDrop\Livewire\Edit;
use App\Modules\PickupDrop\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/pickup-drop', Index::class)
        ->middleware('can:pickup_drop.view')
        ->name('pickup-drop.index');

    Route::get('/pickup-drop/create', Edit::class)
        ->middleware('can:pickup_drop.create')
        ->name('pickup-drop.create');

    Route::get('/pickup-drop/{pickupDrop}/edit', Edit::class)
        ->middleware('can:pickup_drop.update')
        ->name('pickup-drop.edit');
});
