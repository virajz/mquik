<?php

use App\Modules\LossReasonMaster\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'can:loss_reason_master.view'])->group(function () {
    Route::get('/loss-reason-master', Index::class)->name('loss-reason-master.index');
});
