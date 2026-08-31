<?php

use App\Modules\TechnicianBench\Livewire\Index;
use App\Modules\TechnicianBench\Livewire\Response;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/technician-bench', Index::class)
        ->middleware('can:technician_bench.view')
        ->name('technician-bench.index');

    Route::get('/technician-bench/{vehicleInspectionOrder}', Response::class)
        ->middleware('can:technician_bench.view')
        ->name('technician-bench.response');
});
