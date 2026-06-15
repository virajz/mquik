<?php

use App\Modules\FuelTypeMaster\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'can:fuel_type_master.view'])->group(function () {
    Route::get('/fuel-type-master', Index::class)->name('fuel-type-master.index');
});
