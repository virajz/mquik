<?php

use App\Modules\OutsideLabourReturn\Livewire\Edit;
use App\Modules\OutsideLabourReturn\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/outside-labour-return', Index::class)
        ->middleware('can:outside_labour_return.view')->name('outside-labour-return.index');
    Route::get('/outside-labour-return/create', Edit::class)
        ->middleware('can:outside_labour_return.create')->name('outside-labour-return.create');
    Route::get('/outside-labour-return/{outsideLabourReturn}/edit', Edit::class)
        ->middleware('can:outside_labour_return.update')->name('outside-labour-return.edit');
});
