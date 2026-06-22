<?php

use App\Modules\IpoCancellationReasonMaster\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'can:ipo_cancellation_reason_master.view'])->group(function () {
    Route::get('/ipo-cancellation-reason-master', Index::class)->name('ipo-cancellation-reason-master.index');
});
