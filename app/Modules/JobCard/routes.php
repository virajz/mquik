<?php

use App\Modules\JobCard\Livewire\Edit;
use App\Modules\JobCard\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/job-cards', Index::class)
        ->middleware('can:job_card.view')
        ->name('job-card.index');

    Route::get('/job-cards/create', Edit::class)
        ->middleware('can:job_card.create')
        ->name('job-card.create');

    Route::get('/job-cards/{jobCard}/edit', Edit::class)
        ->middleware('can:job_card.update')
        ->name('job-card.edit');
});
