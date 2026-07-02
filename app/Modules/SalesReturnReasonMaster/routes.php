<?php

use App\Modules\SalesReturnReasonMaster\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'can:sales_return_reason_master.view'])->group(function () {
    Route::get('/sales-return-reason-master', Index::class)->name('sales-return-reason-master.index');
});
