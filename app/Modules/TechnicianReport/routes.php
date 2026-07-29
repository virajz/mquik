<?php

use App\Modules\TechnicianReport\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'can:technician_report.view'])->group(function () {
    Route::get('/technician-report', Index::class)->name('technician-report.index');
});
