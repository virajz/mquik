<?php

use App\Modules\PerformanceScore\Livewire\Edit;
use App\Modules\PerformanceScore\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/performance-scores', Index::class)
        ->middleware('can:performance_score.view')
        ->name('performance-score.index');

    Route::get('/performance-scores/create', Edit::class)
        ->middleware('can:performance_score.create')
        ->name('performance-score.create');

    Route::get('/performance-scores/{performanceScore}/edit', Edit::class)
        ->middleware('can:performance_score.update')
        ->name('performance-score.edit');
});
