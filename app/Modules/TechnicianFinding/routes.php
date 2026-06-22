<?php

use App\Modules\TechnicianFinding\Livewire\Edit;
use App\Modules\TechnicianFinding\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/technician-findings', Index::class)
        ->middleware('can:technician_finding.view')
        ->name('technician-finding.index');

    Route::get('/technician-findings/create', Edit::class)
        ->middleware('can:technician_finding.create')
        ->name('technician-finding.create');

    Route::get('/technician-findings/{technicianFinding}/edit', Edit::class)
        ->middleware('can:technician_finding.update')
        ->name('technician-finding.edit');
});
