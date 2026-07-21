<?php

use App\Modules\ParkingSlotMaster\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'can:parking_slot_master.view'])->group(function () {
    Route::get('/parking-slot-master', Index::class)->name('parking-slot-master.index');
});
