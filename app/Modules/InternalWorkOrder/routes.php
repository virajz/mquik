<?php

use App\Modules\InternalWorkOrder\Livewire\Edit;
use App\Modules\InternalWorkOrder\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/internal-work-order', Index::class)
        ->middleware('can:internal_work_order.view')->name('internal-work-order.index');
    Route::get('/internal-work-order/create', Edit::class)
        ->middleware('can:internal_work_order.create')->name('internal-work-order.create');
    Route::get('/internal-work-order/{internalWorkOrder}/edit', Edit::class)
        ->middleware('can:internal_work_order.update')->name('internal-work-order.edit');
});
