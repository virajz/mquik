<?php

use App\Modules\CancelReasonMaster\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'can:cancel_reason_master.view'])->group(function () {
    Route::get('/cancel-reason-master', Index::class)->name('cancel-reason-master.index');
});
