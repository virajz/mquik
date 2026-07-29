<?php

use App\Modules\GatePassApproval\Livewire\Edit;
use App\Modules\GatePassApproval\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/gate-pass-approval', Index::class)
        ->middleware('can:gate_pass_approval.view')->name('gate-pass-approval.index');
    Route::get('/gate-pass-approval/create', Edit::class)
        ->middleware('can:gate_pass_approval.create')->name('gate-pass-approval.create');
    Route::get('/gate-pass-approval/{gatePassApproval}/edit', Edit::class)
        ->middleware('can:gate_pass_approval.update')->name('gate-pass-approval.edit');
});
