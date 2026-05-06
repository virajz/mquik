<?php

use App\Modules\WorkshopDepartmentMaster\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/workshop-department-master', Index::class)->name('workshop-department-master.index');
});
