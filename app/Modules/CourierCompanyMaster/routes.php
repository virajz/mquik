<?php

use App\Modules\CourierCompanyMaster\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'can:courier_company_master.view'])->group(function () {
    Route::get('/courier-company-master', Index::class)->name('courier-company-master.index');
});
