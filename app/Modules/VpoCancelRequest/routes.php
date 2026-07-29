<?php

use App\Modules\VpoCancelRequest\Livewire\Edit;
use App\Modules\VpoCancelRequest\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/vpo-cancel-request', Index::class)
        ->middleware('can:vpo_cancel_request.view')->name('vpo-cancel-request.index');
    Route::get('/vpo-cancel-request/create', Edit::class)
        ->middleware('can:vpo_cancel_request.create')->name('vpo-cancel-request.create');
    Route::get('/vpo-cancel-request/{vpoCancelRequest}/edit', Edit::class)
        ->middleware('can:vpo_cancel_request.update')->name('vpo-cancel-request.edit');
});
