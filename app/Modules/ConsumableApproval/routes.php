<?php

use App\Modules\ConsumableApproval\Livewire\Edit;
use App\Modules\ConsumableApproval\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/consumable-approval', Index::class)
        ->middleware('can:consumable_approval.view')->name('consumable-approval.index');
    Route::get('/consumable-approval/create', Edit::class)
        ->middleware('can:consumable_approval.create')->name('consumable-approval.create');
    Route::get('/consumable-approval/{consumableApproval}/edit', Edit::class)
        ->middleware('can:consumable_approval.update')->name('consumable-approval.edit');
});
