<?php

use App\Modules\JobCardCancelReasonMaster\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/job-card-cancel-reason-master', Index::class)->name('job-card-cancel-reason-master.index');
});
