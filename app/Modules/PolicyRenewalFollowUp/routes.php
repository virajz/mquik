<?php

use App\Modules\PolicyRenewalFollowUp\Livewire\Edit;
use App\Modules\PolicyRenewalFollowUp\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/policy-renewal-follow-up', Index::class)
        ->middleware('can:policy_renewal_follow_up.view')->name('policy-renewal-follow-up.index');
    Route::get('/policy-renewal-follow-up/create', Edit::class)
        ->middleware('can:policy_renewal_follow_up.create')->name('policy-renewal-follow-up.create');
    Route::get('/policy-renewal-follow-up/{policyRenewalFollowUp}/edit', Edit::class)
        ->middleware('can:policy_renewal_follow_up.update')->name('policy-renewal-follow-up.edit');
});
