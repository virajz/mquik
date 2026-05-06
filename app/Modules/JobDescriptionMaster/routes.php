<?php

use App\Modules\JobDescriptionMaster\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/job-description-master', Index::class)->name('job-description-master.index');
});
