<?php

use App\Modules\ServiceIntervalMaster\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/service-interval-master', Index::class)->name('service-interval-master.index');
});
