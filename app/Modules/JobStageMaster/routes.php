<?php

use App\Modules\JobStageMaster\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'can:job_stage_master.view'])->group(function () {
    Route::get('/job-stage-master', Index::class)->name('job-stage-master.index');
});
