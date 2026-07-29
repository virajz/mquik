<?php

use App\Modules\DeliveryOrder\Livewire\Edit;
use App\Modules\DeliveryOrder\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/delivery-order', Index::class)
        ->middleware('can:delivery_order.view')->name('delivery-order.index');
    Route::get('/delivery-order/create', Edit::class)
        ->middleware('can:delivery_order.create')->name('delivery-order.create');
    Route::get('/delivery-order/{deliveryOrder}/edit', Edit::class)
        ->middleware('can:delivery_order.update')->name('delivery-order.edit');
});
