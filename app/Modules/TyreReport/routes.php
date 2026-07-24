<?php

use App\Modules\TyreReport\Livewire\Edit;
use App\Modules\TyreReport\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/tyre-reports', Index::class)
        ->middleware('can:tyre_report.view')->name('tyre-report.index');
    Route::get('/tyre-reports/create', Edit::class)
        ->middleware('can:tyre_report.create')->name('tyre-report.create');
    Route::get('/tyre-reports/{tyreReport}/edit', Edit::class)
        ->middleware('can:tyre_report.update')->name('tyre-report.edit');
});
