<?php

use App\Modules\VpoApproval\Livewire\Edit;
use App\Modules\VpoApproval\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/vpo-approval', Index::class)
        ->middleware('can:vpo_approval.view')->name('vpo-approval.index');
    Route::get('/vpo-approval/create', Edit::class)
        ->middleware('can:vpo_approval.create')->name('vpo-approval.create');
    Route::get('/vpo-approval/{vpoApproval}/edit', Edit::class)
        ->middleware('can:vpo_approval.update')->name('vpo-approval.edit');
});
