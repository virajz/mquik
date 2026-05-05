<?php

use App\Modules\CustomerMaster\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/customer-master', Index::class)->name('customer-master.index');
});
