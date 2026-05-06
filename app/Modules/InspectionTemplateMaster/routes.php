<?php

use App\Modules\InspectionTemplateMaster\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/inspection-template-master', Index::class)->name('inspection-template-master.index');
});
