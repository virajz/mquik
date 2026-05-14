<?php

use App\Modules\SmartSalary\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/smart-salary', Index::class)->name('smart-salary.index');
});
