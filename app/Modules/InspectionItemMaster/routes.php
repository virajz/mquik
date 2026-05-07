<?php

use App\Modules\InspectionItemMaster\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'can:inspection_item_master.view'])->group(function () {
    Route::get('/inspection-item-master', Index::class)->name('inspection-item-master.index');
});
