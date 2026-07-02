<?php

use App\Modules\ConsumableCategoryMaster\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'can:consumable_category_master.view'])->group(function () {
    Route::get('/consumable-category-master', Index::class)->name('consumable-category-master.index');
});
