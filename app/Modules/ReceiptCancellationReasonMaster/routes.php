<?php

use App\Modules\ReceiptCancellationReasonMaster\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'can:receipt_cancellation_reason_master.view'])->group(function () {
    Route::get('/receipt-cancellation-reason-master', Index::class)->name('receipt-cancellation-reason-master.index');
});
