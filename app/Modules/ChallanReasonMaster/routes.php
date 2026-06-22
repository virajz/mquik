<?php

use App\Modules\ChallanReasonMaster\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'can:challan_reason_master.view'])->group(function () {
    Route::get('/challan-reason-master', Index::class)->name('challan-reason-master.index');
});
