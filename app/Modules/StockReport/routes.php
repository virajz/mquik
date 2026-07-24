<?php

use App\Modules\StockReport\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'can:stock_report.view'])->group(function () {
    Route::get('/stock-report', Index::class)->name('stock-report.index');
});
