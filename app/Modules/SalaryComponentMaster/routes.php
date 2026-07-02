<?php

use App\Modules\SalaryComponentMaster\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'can:salary_component_master.view'])->group(function () {
    Route::get('/salary-component-master', Index::class)->name('salary-component-master.index');
});
