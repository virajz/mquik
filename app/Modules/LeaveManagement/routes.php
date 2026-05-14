<?php

use App\Modules\LeaveManagement\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/leave-management', Index::class)->name('leave-management.index');
});
