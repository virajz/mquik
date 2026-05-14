<?php

use App\Modules\LateMemo\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/late-memo', Index::class)->name('late-memo.index');
});
