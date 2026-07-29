<?php

use App\Modules\GoodsHandover\Livewire\Edit;
use App\Modules\GoodsHandover\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/goods-handover', Index::class)
        ->middleware('can:goods_handover.view')->name('goods-handover.index');
    Route::get('/goods-handover/create', Edit::class)
        ->middleware('can:goods_handover.create')->name('goods-handover.create');
    Route::get('/goods-handover/{goodsHandover}/edit', Edit::class)
        ->middleware('can:goods_handover.update')->name('goods-handover.edit');
});
