<?php

use App\Modules\ProformaApproval\Livewire\Edit;
use App\Modules\ProformaApproval\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/proforma-approval', Index::class)
        ->middleware('can:proforma_approval.view')->name('proforma-approval.index');
    Route::get('/proforma-approval/create', Edit::class)
        ->middleware('can:proforma_approval.create')->name('proforma-approval.create');
    Route::get('/proforma-approval/{proformaApproval}/edit', Edit::class)
        ->middleware('can:proforma_approval.update')->name('proforma-approval.edit');
});
