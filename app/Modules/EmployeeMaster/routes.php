<?php

use App\Modules\EmployeeMaster\Livewire\Edit;
use App\Modules\EmployeeMaster\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/employee-master', Index::class)
        ->middleware('can:employee_master.view')->name('employee-master.index');
    Route::get('/employee-master/create', Edit::class)
        ->middleware('can:employee_master.create')->name('employee-master.create');
    Route::get('/employee-master/{employeeMaster}/edit', Edit::class)
        ->middleware('can:employee_master.update')->name('employee-master.edit');
});
