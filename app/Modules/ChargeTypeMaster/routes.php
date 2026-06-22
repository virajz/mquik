<?php

use App\Modules\ChargeTypeMaster\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'can:charge_type_master.view'])->group(function () {
    Route::get('/charge-type-master', Index::class)->name('charge-type-master.index');
});
