<?php

use App\Modules\AmcServiceFollowUp\Livewire\Edit;
use App\Modules\AmcServiceFollowUp\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/amc-service-follow-up', Index::class)
        ->middleware('can:amc_service_follow_up.view')->name('amc-service-follow-up.index');
    Route::get('/amc-service-follow-up/create', Edit::class)
        ->middleware('can:amc_service_follow_up.create')->name('amc-service-follow-up.create');
    Route::get('/amc-service-follow-up/{amcServiceFollowUp}/edit', Edit::class)
        ->middleware('can:amc_service_follow_up.update')->name('amc-service-follow-up.edit');
});
