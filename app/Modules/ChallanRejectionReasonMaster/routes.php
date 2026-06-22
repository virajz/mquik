<?php

use App\Modules\ChallanRejectionReasonMaster\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'can:challan_rejection_reason_master.view'])->group(function () {
    Route::get('/challan-rejection-reason-master', Index::class)->name('challan-rejection-reason-master.index');
});
