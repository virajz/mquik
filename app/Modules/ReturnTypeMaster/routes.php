<?php

use App\Modules\ReturnTypeMaster\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'can:return_type_master.view'])->group(function () {
    Route::get('/return-type-master', Index::class)->name('return-type-master.index');
});
