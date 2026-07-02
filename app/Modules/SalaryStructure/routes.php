<?php

use App\Modules\SalaryStructure\Livewire\Edit;
use App\Modules\SalaryStructure\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/salary-structures', Index::class)
        ->middleware('can:salary_structure.view')
        ->name('salary-structure.index');

    Route::get('/salary-structures/create', Edit::class)
        ->middleware('can:salary_structure.create')
        ->name('salary-structure.create');

    Route::get('/salary-structures/{salaryStructure}/edit', Edit::class)
        ->middleware('can:salary_structure.update')
        ->name('salary-structure.edit');
});
