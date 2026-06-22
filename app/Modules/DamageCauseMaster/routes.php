<?php

use App\Modules\DamageCauseMaster\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'can:damage_cause_master.view'])->group(function () {
    Route::get('/damage-cause-master', Index::class)->name('damage-cause-master.index');
});
