<?php

use App\Modules\TaxMaster\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'can:tax_master.view'])->group(function () {
    Route::get('/tax-master', Index::class)->name('tax-master.index');
});
