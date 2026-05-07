<?php

use App\Modules\BankMaster\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'can:bank_master.view'])->group(function () {
    Route::get('/bank-master', Index::class)->name('bank-master.index');
});
