<?php

use App\Modules\ConsumableDepartmentMaster\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/consumable-department-master', Index::class)->name('consumable-department-master.index');
});
