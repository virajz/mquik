<?php

use App\Modules\ServiceDueFollowUp\Livewire\Edit;
use App\Modules\ServiceDueFollowUp\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/service-due-follow-up', Index::class)
        ->middleware('can:service_due_follow_up.view')->name('service-due-follow-up.index');
    Route::get('/service-due-follow-up/create', Edit::class)
        ->middleware('can:service_due_follow_up.create')->name('service-due-follow-up.create');
    Route::get('/service-due-follow-up/{serviceDueFollowUp}/edit', Edit::class)
        ->middleware('can:service_due_follow_up.update')->name('service-due-follow-up.edit');
});
