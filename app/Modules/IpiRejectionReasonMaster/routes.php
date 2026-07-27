<?php

use App\Modules\IpiRejectionReasonMaster\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'can:ipi_rejection_reason_master.view'])->group(function () {
    Route::get('/ipi-rejection-reason-master', Index::class)->name('ipi-rejection-reason-master.index');
});
