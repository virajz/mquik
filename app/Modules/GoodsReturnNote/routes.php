<?php

use App\Modules\GoodsReturnNote\Livewire\Edit;
use App\Modules\GoodsReturnNote\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/goods-return-note', Index::class)
        ->middleware('can:goods_return_note.view')->name('goods-return-note.index');
    Route::get('/goods-return-note/create', Edit::class)
        ->middleware('can:goods_return_note.create')->name('goods-return-note.create');
    Route::get('/goods-return-note/{outsideLabourReturn}/edit', Edit::class)
        ->middleware('can:goods_return_note.update')->name('goods-return-note.edit');
});
