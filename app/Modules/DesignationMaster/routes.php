<?php

use App\Modules\DesignationMaster\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'can:designation_master.view'])->group(function () {
    Route::get('/designation-master', Index::class)->name('designation-master.index');
});
