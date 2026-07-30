<?php

use App\Modules\CustomerMaster\Http\CustomerDocumentController;
use App\Modules\CustomerMaster\Livewire\Edit;
use App\Modules\CustomerMaster\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/customer-master', Index::class)
        ->middleware('can:customer_master.view')
        ->name('customer-master.index');

    Route::get('/customer-master/create', Edit::class)
        ->middleware('can:customer_master.create')
        ->name('customer-master.create');

    Route::get('/customer-master/{customer}/edit', Edit::class)
        ->middleware('can:customer_master.update')
        ->name('customer-master.edit');

    Route::get('/customer-master/{customer}/file/{type}', CustomerDocumentController::class)
        ->middleware('can:customer_master.view')
        ->whereIn('type', ['aadhar', 'pan', 'gst_certificate'])
        ->name('customer-master.file');
});
