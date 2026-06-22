<?php

use App\Modules\IpoRejectionReasonMaster\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'can:ipo_rejection_reason_master.view'])->group(function () {
    Route::get('/ipo-rejection-reason-master', Index::class)->name('ipo-rejection-reason-master.index');
});
