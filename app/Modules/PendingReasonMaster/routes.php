<?php

use App\Modules\PendingReasonMaster\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'can:pending_reason_master.view'])->group(function () {
    Route::get('/pending-reason-master', Index::class)->name('pending-reason-master.index');
});
