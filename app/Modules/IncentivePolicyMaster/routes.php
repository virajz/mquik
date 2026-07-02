<?php

use App\Modules\IncentivePolicyMaster\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'can:incentive_policy_master.view'])->group(function () {
    Route::get('/incentive-policy-master', Index::class)->name('incentive-policy-master.index');
});
