<?php

use App\Modules\EmployeeMaster\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/employee-master', Index::class)->name('employee-master.index');
});
