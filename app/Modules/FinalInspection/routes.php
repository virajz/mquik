<?php

use App\Modules\FinalInspection\Livewire\Edit;
use App\Modules\FinalInspection\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/final-inspections', Index::class)
        ->middleware('can:final_inspection.view')
        ->name('final-inspection.index');

    Route::get('/final-inspections/create', Edit::class)
        ->middleware('can:final_inspection.create')
        ->name('final-inspection.create');

    Route::get('/final-inspections/{finalInspection}/edit', Edit::class)
        ->middleware('can:final_inspection.update')
        ->name('final-inspection.edit');
});
