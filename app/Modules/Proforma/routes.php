<?php

use App\Modules\Proforma\Livewire\Edit;
use App\Modules\Proforma\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/proformas', Index::class)
        ->middleware('can:proforma.view')
        ->name('proforma.index');

    Route::get('/proformas/create', Edit::class)
        ->middleware('can:proforma.create')
        ->name('proforma.create');

    Route::get('/proformas/{proforma}/edit', Edit::class)
        ->middleware('can:proforma.update')
        ->name('proforma.edit');
});
