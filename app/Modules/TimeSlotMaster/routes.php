<?php

use App\Modules\TimeSlotMaster\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'can:time_slot_master.view'])->group(function () {
    Route::get('/time-slot-master', Index::class)->name('time-slot-master.index');
});
