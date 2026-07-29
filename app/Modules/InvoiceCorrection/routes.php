<?php

use App\Modules\InvoiceCorrection\Livewire\Edit;
use App\Modules\InvoiceCorrection\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/invoice-correction', Index::class)
        ->middleware('can:invoice_correction.view')->name('invoice-correction.index');
    Route::get('/invoice-correction/create', Edit::class)
        ->middleware('can:invoice_correction.create')->name('invoice-correction.create');
    Route::get('/invoice-correction/{invoiceCorrection}/edit', Edit::class)
        ->middleware('can:invoice_correction.update')->name('invoice-correction.edit');
});
