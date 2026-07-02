<?php

use App\Modules\InvoiceCancellationReasonMaster\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'can:invoice_cancellation_reason_master.view'])->group(function () {
    Route::get('/invoice-cancellation-reason-master', Index::class)->name('invoice-cancellation-reason-master.index');
});
