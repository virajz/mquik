<?php

use App\Modules\DigitalInspection\Livewire\Edit;
use App\Modules\DigitalInspection\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/digital-inspections', Index::class)
        ->middleware('can:digital_inspection.view')
        ->name('digital-inspection.index');

    Route::get('/digital-inspections/create', Edit::class)
        ->middleware('can:digital_inspection.create')
        ->name('digital-inspection.create');

    Route::get('/digital-inspections/{digitalInspection}/edit', Edit::class)
        ->middleware('can:digital_inspection.update')
        ->name('digital-inspection.edit');
});
