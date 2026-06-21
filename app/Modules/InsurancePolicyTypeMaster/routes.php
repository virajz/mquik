<?php

use App\Modules\InsurancePolicyTypeMaster\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'can:insurance_policy_type_master.view'])->group(function () {
    Route::get('/insurance-policy-type-master', Index::class)->name('insurance-policy-type-master.index');
});
