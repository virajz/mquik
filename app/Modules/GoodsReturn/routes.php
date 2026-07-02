<?php

use App\Modules\GoodsReturn\Livewire\Edit;
use App\Modules\GoodsReturn\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/goods-returns', Index::class)
        ->middleware('can:goods_return.view')
        ->name('goods-return.index');

    Route::get('/goods-returns/create', Edit::class)
        ->middleware('can:goods_return.create')
        ->name('goods-return.create');

    Route::get('/goods-returns/{goodsReturn}/edit', Edit::class)
        ->middleware('can:goods_return.update')
        ->name('goods-return.edit');
});
