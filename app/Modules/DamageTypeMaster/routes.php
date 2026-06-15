<?php

use App\Modules\DamageTypeMaster\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'can:damage_type_master.view'])->group(function () {
    Route::get('/damage-type-master', Index::class)->name('damage-type-master.index');
});
