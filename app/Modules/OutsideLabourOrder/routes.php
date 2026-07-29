<?php

use App\Modules\OutsideLabourOrder\Livewire\Edit;
use App\Modules\OutsideLabourOrder\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/outside-labour-orders', Index::class)
        ->middleware('can:outside_labour_order.view')
        ->name('outside-labour-order.index');

    Route::get('/outside-labour-orders/create', Edit::class)
        ->middleware('can:outside_labour_order.create')
        ->name('outside-labour-order.create');

    Route::get('/outside-labour-orders/{outsideLabourOrder}/edit', Edit::class)
        ->middleware('can:outside_labour_order.update')
        ->name('outside-labour-order.edit');
});
