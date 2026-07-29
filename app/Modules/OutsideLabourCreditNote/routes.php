<?php

use App\Modules\OutsideLabourCreditNote\Livewire\Edit;
use App\Modules\OutsideLabourCreditNote\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/outside-labour-credit-note', Index::class)
        ->middleware('can:outside_labour_credit_note.view')->name('outside-labour-credit-note.index');
    Route::get('/outside-labour-credit-note/create', Edit::class)
        ->middleware('can:outside_labour_credit_note.create')->name('outside-labour-credit-note.create');
    Route::get('/outside-labour-credit-note/{outsideLabourCreditNote}/edit', Edit::class)
        ->middleware('can:outside_labour_credit_note.update')->name('outside-labour-credit-note.edit');
});
