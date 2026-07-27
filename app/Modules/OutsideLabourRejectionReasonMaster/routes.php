<?php

use App\Modules\OutsideLabourRejectionReasonMaster\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'can:outside_labour_rejection_reason_master.view'])->group(function () {
    Route::get('/outside-labour-rejection-reason-master', Index::class)->name('outside-labour-rejection-reason-master.index');
});
