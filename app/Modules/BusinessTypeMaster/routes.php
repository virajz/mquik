<?php

use App\Modules\BusinessTypeMaster\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'can:business_type_master.view'])->group(function () {
    Route::get('/business-type-master', Index::class)->name('business-type-master.index');
});
