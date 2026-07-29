<?php

use App\Modules\GoodsReceipt\Livewire\Edit;
use App\Modules\GoodsReceipt\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/goods-receipt', Index::class)
        ->middleware('can:goods_receipt.view')->name('goods-receipt.index');
    Route::get('/goods-receipt/create', Edit::class)
        ->middleware('can:goods_receipt.create')->name('goods-receipt.create');
    Route::get('/goods-receipt/{goodsReceipt}/edit', Edit::class)
        ->middleware('can:goods_receipt.update')->name('goods-receipt.edit');
});
