<?php

use App\Modules\ServiceTypeMaster\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/service-type-master', Index::class)->name('service-type-master.index');
});
