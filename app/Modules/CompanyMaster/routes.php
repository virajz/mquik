<?php

use App\Modules\CompanyMaster\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'can:company_master.view'])->group(function () {
    Route::get('/company-master', Index::class)->name('company-master.index');
});
