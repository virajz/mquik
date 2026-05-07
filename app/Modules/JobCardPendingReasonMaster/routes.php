<?php

use App\Modules\JobCardPendingReasonMaster\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'can:job_card_pending_reason_master.view'])->group(function () {
    Route::get('/job-card-pending-reason-master', Index::class)->name('job-card-pending-reason-master.index');
});
