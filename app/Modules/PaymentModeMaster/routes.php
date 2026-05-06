<?php

use App\Modules\PaymentModeMaster\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/payment-mode-master', Index::class)->name('payment-mode-master.index');
});
