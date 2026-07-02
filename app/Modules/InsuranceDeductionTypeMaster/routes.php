<?php

use App\Modules\InsuranceDeductionTypeMaster\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'can:insurance_deduction_type_master.view'])->group(function () {
    Route::get('/insurance-deduction-type-master', Index::class)->name('insurance-deduction-type-master.index');
});
