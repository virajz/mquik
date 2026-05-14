<?php

use App\Modules\Attendance\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/attendance', Index::class)->name('attendance.index');
});
