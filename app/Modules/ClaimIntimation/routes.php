<?php

use App\Modules\ClaimIntimation\Livewire\Edit;
use App\Modules\ClaimIntimation\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/claim-intimation', Index::class)
        ->middleware('can:claim_intimation.view')->name('claim-intimation.index');
    Route::get('/claim-intimation/create', Edit::class)
        ->middleware('can:claim_intimation.create')->name('claim-intimation.create');
    Route::get('/claim-intimation/{claimIntimation}/edit', Edit::class)
        ->middleware('can:claim_intimation.update')->name('claim-intimation.edit');
});
