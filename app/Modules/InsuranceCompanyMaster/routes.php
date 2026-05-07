<?php

use App\Modules\InsuranceCompanyMaster\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'can:insurance_company_master.view'])->group(function () {
    Route::get('/insurance-company-master', Index::class)->name('insurance-company-master.index');
});
