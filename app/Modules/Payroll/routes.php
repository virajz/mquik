<?php

use App\Modules\Payroll\Livewire\Edit;
use App\Modules\Payroll\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/payroll', Index::class)
        ->middleware('can:payroll.view')->name('payroll.index');
    Route::get('/payroll/create', Edit::class)
        ->middleware('can:payroll.create')->name('payroll.create');
    Route::get('/payroll/{payroll}/edit', Edit::class)
        ->middleware('can:payroll.update')->name('payroll.edit');
});
