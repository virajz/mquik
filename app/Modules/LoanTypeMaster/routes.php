<?php

use App\Modules\LoanTypeMaster\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'can:loan_type_master.view'])->group(function () {
    Route::get('/loan-type-master', Index::class)->name('loan-type-master.index');
});
