<?php

use App\Modules\ReworkReasonMaster\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'can:rework_reason_master.view'])->group(function () {
    Route::get('/rework-reason-master', Index::class)->name('rework-reason-master.index');
});
