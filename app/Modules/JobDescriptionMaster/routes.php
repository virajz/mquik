<?php

use App\Modules\JobDescriptionMaster\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'can:job_description_master.view'])->group(function () {
    Route::get('/job-description-master', Index::class)->name('job-description-master.index');
});
