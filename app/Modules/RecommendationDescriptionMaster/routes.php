<?php

use App\Modules\RecommendationDescriptionMaster\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'can:recommendation_description_master.view'])->group(function () {
    Route::get('/recommendation-description-master', Index::class)->name('recommendation-description-master.index');
});
