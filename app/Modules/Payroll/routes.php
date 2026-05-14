<?php

use App\Modules\Payroll\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/payroll', Index::class)->name('payroll.index');
});
