<?php

use App\Modules\TransmissionTypeMaster\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'can:transmission_type_master.view'])->group(function () {
    Route::get('/transmission-type-master', Index::class)->name('transmission-type-master.index');
});
