<?php

use App\Modules\ExcessStockApproval\Livewire\Edit;
use App\Modules\ExcessStockApproval\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/excess-stock-approval', Index::class)
        ->middleware('can:excess_stock_approval.view')->name('excess-stock-approval.index');
    Route::get('/excess-stock-approval/create', Edit::class)
        ->middleware('can:excess_stock_approval.create')->name('excess-stock-approval.create');
    Route::get('/excess-stock-approval/{excessStockApproval}/edit', Edit::class)
        ->middleware('can:excess_stock_approval.update')->name('excess-stock-approval.edit');
});
