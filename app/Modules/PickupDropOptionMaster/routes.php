<?php

use App\Modules\PickupDropOptionMaster\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'can:pickup_drop_option_master.view'])->group(function () {
    Route::get('/pickup-drop-option-master', Index::class)->name('pickup-drop-option-master.index');
});
