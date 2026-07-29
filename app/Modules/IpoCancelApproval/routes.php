<?php

use App\Modules\IpoCancelApproval\Livewire\Edit;
use App\Modules\IpoCancelApproval\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/ipo-cancel-approval', Index::class)
        ->middleware('can:ipo_cancel_approval.view')->name('ipo-cancel-approval.index');
    Route::get('/ipo-cancel-approval/create', Edit::class)
        ->middleware('can:ipo_cancel_approval.create')->name('ipo-cancel-approval.create');
    Route::get('/ipo-cancel-approval/{ipoCancelApproval}/edit', Edit::class)
        ->middleware('can:ipo_cancel_approval.update')->name('ipo-cancel-approval.edit');
});
