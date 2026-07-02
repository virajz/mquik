<?php

use App\Modules\RegularSalesInvoice\Livewire\Edit;
use App\Modules\RegularSalesInvoice\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/regular-sales-invoices', Index::class)
        ->middleware('can:regular_sales_invoice.view')
        ->name('regular-sales-invoice.index');

    Route::get('/regular-sales-invoices/create', Edit::class)
        ->middleware('can:regular_sales_invoice.create')
        ->name('regular-sales-invoice.create');

    Route::get('/regular-sales-invoices/{regularSalesInvoice}/edit', Edit::class)
        ->middleware('can:regular_sales_invoice.update')
        ->name('regular-sales-invoice.edit');
});
