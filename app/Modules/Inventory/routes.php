<?php

use App\Modules\Inventory\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/inventory', Index::class)
        ->middleware('can:inventory.view')
        ->name('inventory.index');
});
