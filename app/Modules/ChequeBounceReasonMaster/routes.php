<?php

use App\Modules\ChequeBounceReasonMaster\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'can:cheque_bounce_reason_master.view'])->group(function () {
    Route::get('/cheque-bounce-reason-master', Index::class)->name('cheque-bounce-reason-master.index');
});
