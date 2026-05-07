<?php

use App\Modules\CustomerMaster\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'can:customer_master.view'])->group(function () {
    Route::get('/customer-master', Index::class)->name('customer-master.index');
});
