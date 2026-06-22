<?php

use App\Modules\DelayReasonMaster\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'can:delay_reason_master.view'])->group(function () {
    Route::get('/delay-reason-master', Index::class)->name('delay-reason-master.index');
});
