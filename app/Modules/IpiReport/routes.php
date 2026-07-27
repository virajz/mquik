<?php

use App\Modules\IpiReport\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'can:ipi_report.view'])->group(function () {
    Route::get('/ipi-report', Index::class)->name('ipi-report.index');
});
