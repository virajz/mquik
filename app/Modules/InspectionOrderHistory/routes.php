<?php

use App\Modules\InspectionOrderHistory\Livewire\Index;
use App\Modules\InspectionOrderHistory\Livewire\TechnicianTat;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'can:inspection_order_history.view'])->group(function () {
    Route::get('/inspection-order-history', Index::class)->name('inspection-order-history.index');
    Route::get('/inspection-order-tat', TechnicianTat::class)->name('inspection-order-tat.index');
});
