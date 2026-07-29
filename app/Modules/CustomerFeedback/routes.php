<?php

use App\Modules\CustomerFeedback\Livewire\Edit;
use App\Modules\CustomerFeedback\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/customer-feedback', Index::class)
        ->middleware('can:customer_feedback.view')->name('customer-feedback.index');
    Route::get('/customer-feedback/create', Edit::class)
        ->middleware('can:customer_feedback.create')->name('customer-feedback.create');
    Route::get('/customer-feedback/{customerFeedback}/edit', Edit::class)
        ->middleware('can:customer_feedback.update')->name('customer-feedback.edit');
});
