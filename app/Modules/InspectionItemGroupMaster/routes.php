<?php

use App\Modules\InspectionItemGroupMaster\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/inspection-item-group-master', Index::class)->name('inspection-item-group-master.index');
});
