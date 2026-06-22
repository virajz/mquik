<?php

use App\Modules\PartTypeMaster\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'can:part_type_master.view'])->group(function () {
    Route::get('/part-type-master', Index::class)->name('part-type-master.index');
});
