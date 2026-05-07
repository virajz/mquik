<?php

use App\Modules\VehicleInventoryItemMaster\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'can:vehicle_inventory_item_master.view'])->group(function () {
    Route::get('/vehicle-inventory-item-master', Index::class)->name('vehicle-inventory-item-master.index');
});
