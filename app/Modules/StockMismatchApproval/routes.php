<?php

use App\Modules\StockMismatchApproval\Livewire\Edit;
use App\Modules\StockMismatchApproval\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/stock-mismatch-approval', Index::class)
        ->middleware('can:stock_mismatch_approval.view')->name('stock-mismatch-approval.index');
    Route::get('/stock-mismatch-approval/create', Edit::class)
        ->middleware('can:stock_mismatch_approval.create')->name('stock-mismatch-approval.create');
    Route::get('/stock-mismatch-approval/{stockMismatchApproval}/edit', Edit::class)
        ->middleware('can:stock_mismatch_approval.update')->name('stock-mismatch-approval.edit');
});
