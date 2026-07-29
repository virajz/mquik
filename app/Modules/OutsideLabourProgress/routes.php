<?php

use App\Modules\OutsideLabourProgress\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'can:outside_labour_progress.view'])->group(function () {
    Route::get('/outside-labour-progress', Index::class)->name('outside-labour-progress.index');
});
