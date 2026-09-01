<?php

use App\Modules\InspectionReport\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'can:inspection_report.view'])->group(function () {
    Route::get('/inspection-report', Index::class)->name('inspection-report.index');
});
