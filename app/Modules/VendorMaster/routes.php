<?php

use App\Modules\VendorMaster\Http\VendorDocumentController;
use App\Modules\VendorMaster\Livewire\Edit;
use App\Modules\VendorMaster\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/vendor-master', Index::class)
        ->middleware('can:vendor_master.view')
        ->name('vendor-master.index');

    Route::get('/vendor-master/create', Edit::class)
        ->middleware('can:vendor_master.create')
        ->name('vendor-master.create');

    Route::get('/vendor-master/{vendor}/edit', Edit::class)
        ->middleware('can:vendor_master.update')
        ->name('vendor-master.edit');

    Route::get('/vendor-master/{vendor}/file/{type}', VendorDocumentController::class)
        ->middleware('can:vendor_master.view')
        ->whereIn('type', ['aadhar', 'pan'])
        ->name('vendor-master.file');
});
