<?php

use App\Modules\InventorySearch\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'can:inventory_search.view'])->group(function () {
    Route::get('/inventory-search', Index::class)->name('inventory-search.index');
});
