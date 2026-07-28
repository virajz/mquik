<?php

use App\Modules\SalesEstimateApproval\Livewire\Edit;
use App\Modules\SalesEstimateApproval\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/sales-estimate-approval', Index::class)
        ->middleware('can:sales_estimate_approval.view')->name('sales-estimate-approval.index');
    Route::get('/sales-estimate-approval/create', Edit::class)
        ->middleware('can:sales_estimate_approval.create')->name('sales-estimate-approval.create');
    Route::get('/sales-estimate-approval/{salesEstimateApproval}/edit', Edit::class)
        ->middleware('can:sales_estimate_approval.update')->name('sales-estimate-approval.edit');
});
