<?php

use App\Modules\StockCounting\Livewire\Edit;
use App\Modules\StockCounting\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/stock-counting', Index::class)
        ->middleware('can:stock_counting.view')->name('stock-counting.index');
    Route::get('/stock-counting/create', Edit::class)
        ->middleware('can:stock_counting.create')->name('stock-counting.create');
    Route::get('/stock-counting/{stockCount}/edit', Edit::class)
        ->middleware('can:stock_counting.update')->name('stock-counting.edit');
});
