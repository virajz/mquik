<?php

use App\Modules\DesignationMaster\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/designation-master', Index::class)->name('designation-master.index');
});
