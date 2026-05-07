<?php

use App\Modules\GstTypeMaster\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'can:gst_type_master.view'])->group(function () {
    Route::get('/gst-type-master', Index::class)->name('gst-type-master.index');
});
