<?php

use App\Modules\GateInOut\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/gate-in-out', Index::class)
        ->middleware('can:gate_in_out.view')
        ->name('gate-in-out.index');
});
