<?php

use App\Modules\OutsideLabourBill\Livewire\Edit;
use App\Modules\OutsideLabourBill\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/outside-labour-bill', Index::class)
        ->middleware('can:outside_labour_bill.view')->name('outside-labour-bill.index');
    Route::get('/outside-labour-bill/create', Edit::class)
        ->middleware('can:outside_labour_bill.create')->name('outside-labour-bill.create');
    Route::get('/outside-labour-bill/{outsideLabourBill}/edit', Edit::class)
        ->middleware('can:outside_labour_bill.update')->name('outside-labour-bill.edit');
});
