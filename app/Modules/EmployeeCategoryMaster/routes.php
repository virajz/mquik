<?php

use App\Modules\EmployeeCategoryMaster\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'can:employee_category_master.view'])->group(function () {
    Route::get('/employee-category-master', Index::class)->name('employee-category-master.index');
});
