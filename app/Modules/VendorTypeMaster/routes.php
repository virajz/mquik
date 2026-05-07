<?php

use App\Modules\VendorTypeMaster\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'can:vendor_type_master.view'])->group(function () {
    Route::get('/vendor-type-master', Index::class)->name('vendor-type-master.index');
});
