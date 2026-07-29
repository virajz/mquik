<?php

use App\Modules\VendorAdvanceRequest\Livewire\Edit;
use App\Modules\VendorAdvanceRequest\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/vendor-advance-request', Index::class)
        ->middleware('can:vendor_advance_request.view')->name('vendor-advance-request.index');
    Route::get('/vendor-advance-request/create', Edit::class)
        ->middleware('can:vendor_advance_request.create')->name('vendor-advance-request.create');
    Route::get('/vendor-advance-request/{vendorAdvanceRequest}/edit', Edit::class)
        ->middleware('can:vendor_advance_request.update')->name('vendor-advance-request.edit');
});
