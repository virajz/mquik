<?php

use App\Modules\VendorMaster\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/vendor-master', Index::class)->name('vendor-master.index');
});
