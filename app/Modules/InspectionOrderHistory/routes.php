<?php

use App\Modules\InspectionOrderHistory\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'can:inspection_order_history.view'])->group(function () {
    Route::get('/inspection-order-history', Index::class)->name('inspection-order-history.index');
});
