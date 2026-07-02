<?php

use App\Modules\ReceiptDifferenceReasonMaster\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'can:receipt_difference_reason_master.view'])->group(function () {
    Route::get('/receipt-difference-reason-master', Index::class)->name('receipt-difference-reason-master.index');
});
