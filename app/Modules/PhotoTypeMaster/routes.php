<?php

use App\Modules\PhotoTypeMaster\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'can:photo_type_master.view'])->group(function () {
    Route::get('/photo-type-master', Index::class)->name('photo-type-master.index');
});
