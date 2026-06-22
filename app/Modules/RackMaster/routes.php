<?php

use App\Modules\RackMaster\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'can:rack_master.view'])->group(function () {
    Route::get('/rack-master', Index::class)->name('rack-master.index');
});
