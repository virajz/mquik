<?php

use App\Modules\FinalWorkOrder\Livewire\Edit;
use App\Modules\FinalWorkOrder\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/final-work-orders', Index::class)
        ->middleware('can:final_work_order.view')
        ->name('final-work-order.index');

    Route::get('/final-work-orders/create', Edit::class)
        ->middleware('can:final_work_order.create')
        ->name('final-work-order.create');

    Route::get('/final-work-orders/{finalWorkOrder}/edit', Edit::class)
        ->middleware('can:final_work_order.update')
        ->name('final-work-order.edit');
});
