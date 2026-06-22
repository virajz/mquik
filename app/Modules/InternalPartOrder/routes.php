<?php

use App\Modules\InternalPartOrder\Livewire\Edit;
use App\Modules\InternalPartOrder\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/internal-part-orders', Index::class)
        ->middleware('can:internal_part_order.view')
        ->name('internal-part-order.index');

    Route::get('/internal-part-orders/create', Edit::class)
        ->middleware('can:internal_part_order.create')
        ->name('internal-part-order.create');

    Route::get('/internal-part-orders/{internalPartOrder}/edit', Edit::class)
        ->middleware('can:internal_part_order.update')
        ->name('internal-part-order.edit');
});
