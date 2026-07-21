<?php

use App\Modules\PriorityMaster\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'can:priority_master.view'])->group(function () {
    Route::get('/priority-master', Index::class)->name('priority-master.index');
});
