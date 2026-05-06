<?php

use App\Modules\ComplaintTypeMaster\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/complaint-type-master', Index::class)->name('complaint-type-master.index');
});
