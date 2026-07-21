<?php

use App\Modules\BookingChannelMaster\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'can:booking_channel_master.view'])->group(function () {
    Route::get('/booking-channel-master', Index::class)->name('booking-channel-master.index');
});
