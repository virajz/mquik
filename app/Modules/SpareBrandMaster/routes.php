<?php

use App\Modules\SpareBrandMaster\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/spare-brand-master', Index::class)->name('spare-brand-master.index');
});
