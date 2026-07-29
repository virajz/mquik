<?php

use App\Modules\SalesInquiry\Livewire\Edit;
use App\Modules\SalesInquiry\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/sales-inquiry', Index::class)
        ->middleware('can:sales_inquiry.view')->name('sales-inquiry.index');
    Route::get('/sales-inquiry/create', Edit::class)
        ->middleware('can:sales_inquiry.create')->name('sales-inquiry.create');
    Route::get('/sales-inquiry/{salesInquiry}/edit', Edit::class)
        ->middleware('can:sales_inquiry.update')->name('sales-inquiry.edit');
});
