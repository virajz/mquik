<?php

use App\Modules\Consumable\Livewire\Edit;
use App\Modules\Consumable\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/consumables', Index::class)
        ->middleware('can:consumable.view')
        ->name('consumable.index');

    Route::get('/consumables/create', Edit::class)
        ->middleware('can:consumable.create')
        ->name('consumable.create');

    Route::get('/consumables/{consumable}/edit', Edit::class)
        ->middleware('can:consumable.update')
        ->name('consumable.edit');
});
