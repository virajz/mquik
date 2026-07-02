<?php

use App\Modules\CounterSalesInvoice\Livewire\Edit;
use App\Modules\CounterSalesInvoice\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/counter-sales-invoices', Index::class)
        ->middleware('can:counter_sales_invoice.view')
        ->name('counter-sales-invoice.index');

    Route::get('/counter-sales-invoices/create', Edit::class)
        ->middleware('can:counter_sales_invoice.create')
        ->name('counter-sales-invoice.create');

    Route::get('/counter-sales-invoices/{counterSalesInvoice}/edit', Edit::class)
        ->middleware('can:counter_sales_invoice.update')
        ->name('counter-sales-invoice.edit');
});
