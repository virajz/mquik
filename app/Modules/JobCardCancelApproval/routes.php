<?php

use App\Modules\JobCardCancelApproval\Livewire\Edit;
use App\Modules\JobCardCancelApproval\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/job-card-cancel-approval', Index::class)
        ->middleware('can:job_card_cancel_approval.view')->name('job-card-cancel-approval.index');
    Route::get('/job-card-cancel-approval/create', Edit::class)
        ->middleware('can:job_card_cancel_approval.create')->name('job-card-cancel-approval.create');
    Route::get('/job-card-cancel-approval/{jobCardCancelApproval}/edit', Edit::class)
        ->middleware('can:job_card_cancel_approval.update')->name('job-card-cancel-approval.edit');
});
