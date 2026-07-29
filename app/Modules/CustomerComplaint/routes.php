<?php

use App\Modules\CustomerComplaint\Livewire\Edit;
use App\Modules\CustomerComplaint\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/customer-complaint', Index::class)
        ->middleware('can:customer_complaint.view')->name('customer-complaint.index');
    Route::get('/customer-complaint/create', Edit::class)
        ->middleware('can:customer_complaint.create')->name('customer-complaint.create');
    Route::get('/customer-complaint/{customerComplaint}/edit', Edit::class)
        ->middleware('can:customer_complaint.update')->name('customer-complaint.edit');
});
