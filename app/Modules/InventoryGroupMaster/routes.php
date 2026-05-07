<?php

use App\Modules\InventoryGroupMaster\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'can:inventory_group_master.view'])->group(function () {
    Route::get('/inventory-group-master', Index::class)->name('inventory-group-master.index');
});
