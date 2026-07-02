<?php

use App\Modules\HolidayMaster\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'can:holiday_master.view'])->group(function () {
    Route::get('/holiday-master', Index::class)->name('holiday-master.index');
});
