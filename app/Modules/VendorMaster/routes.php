<?php

use App\Modules\VendorMaster\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'can:vendor_master.view'])->group(function () {
    Route::get('/vendor-master', Index::class)->name('vendor-master.index');
});
