<?php

use App\Modules\WorkOrderHoldReasonMaster\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'can:work_order_hold_reason_master.view'])->group(function () {
    Route::get('/work-order-hold-reason-master', Index::class)->name('work-order-hold-reason-master.index');
});
