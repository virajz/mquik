<?php

use App\Modules\GateInOut\Livewire\Edit;
use App\Modules\GateInOut\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/gate-in-out', Index::class)
        ->middleware('can:gate_in_out.view')->name('gate-in-out.index');
    Route::get('/gate-in-out/create', Edit::class)
        ->middleware('can:gate_in_out.create')->name('gate-in-out.create');
    Route::get('/gate-in-out/{gateInOut}/edit', Edit::class)
        ->middleware('can:gate_in_out.update')->name('gate-in-out.edit');
});
