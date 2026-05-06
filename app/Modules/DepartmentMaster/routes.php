<?php

use App\Modules\DepartmentMaster\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/department-master', Index::class)->name('department-master.index');
});
