<?php

use App\Modules\OutsideLabourStatusReport\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'can:outside_labour_status_report.view'])->group(function () {
    Route::get('/outside-labour-status-report', Index::class)->name('outside-labour-status-report.index');
});
