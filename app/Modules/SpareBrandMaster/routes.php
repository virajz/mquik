<?php

use App\Modules\SpareBrandMaster\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'can:spare_brand_master.view'])->group(function () {
    Route::get('/spare-brand-master', Index::class)->name('spare-brand-master.index');
});
