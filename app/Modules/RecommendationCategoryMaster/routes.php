<?php

use App\Modules\RecommendationCategoryMaster\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'can:recommendation_category_master.view'])->group(function () {
    Route::get('/recommendation-category-master', Index::class)->name('recommendation-category-master.index');
});
