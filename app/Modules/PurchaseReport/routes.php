<?php

use App\Modules\PurchaseReport\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'can:purchase_report.view'])->group(function () {
    Route::get('/purchase-report', Index::class)->name('purchase-report.index');
});
