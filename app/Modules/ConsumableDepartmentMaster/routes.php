<?php

use App\Modules\ConsumableDepartmentMaster\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'can:consumable_department_master.view'])->group(function () {
    Route::get('/consumable-department-master', Index::class)->name('consumable-department-master.index');
});
