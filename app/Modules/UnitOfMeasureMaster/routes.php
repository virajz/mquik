<?php

use App\Modules\UnitOfMeasureMaster\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/unit-of-measure-master', Index::class)->name('unit-of-measure-master.index');
});
