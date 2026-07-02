<?php

use App\Modules\EmployeeGradeMaster\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'can:employee_grade_master.view'])->group(function () {
    Route::get('/employee-grade-master', Index::class)->name('employee-grade-master.index');
});
